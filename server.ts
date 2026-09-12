import http from 'http';
import { spawn, execSync } from 'child_process';
import path from 'path';
import fs from 'fs';

const PORT = 3000;
const PHP_PORT = 8080;
const rootDir = process.cwd();

// Download PHP if it doesn't exist
const phpPath = path.join(rootDir, 'php');
if (!fs.existsSync(phpPath)) {
  console.log('[Server] Downloading standalone PHP binary...');
  try {
    execSync('curl -sLO https://dl.static-php.dev/static-php-cli/common/php-8.2.32-cli-linux-x86_64.tar.gz', { cwd: rootDir });
    execSync('tar -xzf php-8.2.32-cli-linux-x86_64.tar.gz', { cwd: rootDir });
    execSync('chmod +x php', { cwd: rootDir });
    execSync('rm php-8.2.32-cli-linux-x86_64.tar.gz', { cwd: rootDir });
    console.log('[Server] PHP binary downloaded successfully.');
  } catch (err) {
    console.error('[Server] Failed to download PHP:', err);
  }
}

// 1. Initialize SQLite database if needed
const sqliteFile = path.join(rootDir, 'database', 'sponsor_app.sqlite');
if (!fs.existsSync(sqliteFile)) {
  console.log('[Server] Initializing database...');
  const initProc = spawn(phpPath, ['database/init_db.php'], { stdio: 'inherit', cwd: rootDir });
  initProc.on('close', (code) => {
    console.log(`[Server] Database initialization completed with code ${code}`);
  });
}

// 2. Start PHP Built-in Server on 127.0.0.1:8080
console.log(`[Server] Spawning PHP Server on port ${PHP_PORT}...`);
const phpServer = spawn(phpPath, ['-S', `127.0.0.1:${PHP_PORT}`, 'router.php'], {
  cwd: rootDir,
  stdio: ['ignore', 'inherit', 'inherit']
});

phpServer.on('error', (err) => {
  console.error('[Server] Failed to start PHP server:', err);
});

phpServer.on('exit', (code, signal) => {
  console.log(`[Server] PHP server exited with code ${code}, signal ${signal}`);
});

process.on('SIGTERM', () => phpServer.kill('SIGTERM'));
process.on('SIGINT', () => phpServer.kill('SIGINT'));

// 3. Reverse Proxy HTTP Server on Port 3000
const server = http.createServer((clientReq, clientRes) => {
  const forwardedProto = (clientReq.headers['x-forwarded-proto'] as string) || 'https';
  const options: http.RequestOptions = {
    hostname: '127.0.0.1',
    port: PHP_PORT,
    path: clientReq.url,
    method: clientReq.method,
    headers: {
      ...clientReq.headers,
      host: `127.0.0.1:${PHP_PORT}`,
      'x-forwarded-for': clientReq.socket.remoteAddress || '',
      'x-forwarded-proto': forwardedProto,
      'x-forwarded-port': '3000'
    }
  };

  const phpReq = http.request(options, (phpRes) => {
    const headers = { ...phpRes.headers };

    // Rewrite Location header so browser stays on relative paths or current host
    if (headers.location) {
      if (typeof headers.location === 'string') {
        headers.location = headers.location.replace(`http://127.0.0.1:${PHP_PORT}`, '');
        if (headers.location === '') headers.location = '/';
      }
    }

    // Ensure all cookies have SameSite=None; Secure; Partitioned for cross-site iframe preview compatibility
    if (headers['set-cookie']) {
      const cookies = Array.isArray(headers['set-cookie'])
        ? headers['set-cookie']
        : [headers['set-cookie']];

      headers['set-cookie'] = cookies.map((cookieStr) => {
        let mod = cookieStr;
        if (!/;\s*path=/i.test(mod)) {
          mod += '; path=/';
        }
        if (!/;\s*SameSite=/i.test(mod)) {
          mod += '; SameSite=None';
        } else {
          mod = mod.replace(/;\s*SameSite=[^;]+/i, '; SameSite=None');
        }
        if (!/;\s*Secure/i.test(mod)) {
          mod += '; Secure';
        }
        if (!/;\s*Partitioned/i.test(mod)) {
          mod += '; Partitioned';
        }
        return mod;
      });
    }

    clientRes.writeHead(phpRes.statusCode || 200, headers);
    phpRes.pipe(clientRes);
  });

  phpReq.on('error', (err) => {
    console.error('[Proxy Error]:', err.message);
    clientRes.writeHead(502, { 'Content-Type': 'text/plain' });
    clientRes.end('502 Bad Gateway: PHP server warming up or unavailable.');
  });

  clientReq.pipe(phpReq);
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`[Server] Sponsor App Proxy Server running at http://0.0.0.0:${PORT}`);
});
