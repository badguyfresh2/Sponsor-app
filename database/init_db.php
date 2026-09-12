<?php
// Initialize database for SQLite (standalone & preview) and verify schemas
$dbFile = __DIR__ . '/sponsor_app.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'sponsor',
        phone TEXT,
        avatar_url TEXT,
        status TEXT NOT NULL DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS sponsor_profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        bio TEXT,
        location TEXT,
        address TEXT,
        country TEXT DEFAULT 'Uganda',
        preferred_currency TEXT DEFAULT 'UGX',
        monthly_budget REAL DEFAULT 0.00,
        anonymous_mode INTEGER DEFAULT 0,
        notification_email INTEGER DEFAULT 1,
        notification_sms INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS organizations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        org_name TEXT NOT NULL,
        org_type TEXT DEFAULT 'Non-Profit NGO',
        description TEXT,
        location TEXT DEFAULT 'Kampala, Uganda',
        logo_url TEXT,
        verified INTEGER DEFAULT 1,
        rating REAL DEFAULT 4.90,
        contact_email TEXT NOT NULL,
        contact_phone TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS beneficiaries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        org_id INTEGER NOT NULL,
        full_name TEXT NOT NULL,
        age INTEGER NOT NULL,
        gender TEXT NOT NULL,
        date_of_birth TEXT,
        location TEXT NOT NULL,
        bio TEXT NOT NULL,
        story TEXT NOT NULL,
        photo_url TEXT NOT NULL,
        category TEXT DEFAULT 'Education',
        school_grade TEXT,
        dreams TEXT,
        health_status TEXT DEFAULT 'Good health',
        monthly_need_amount REAL DEFAULT 150000.00,
        current_supported_amount REAL DEFAULT 0.00,
        status TEXT DEFAULT 'available',
        urgent_flag INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS beneficiary_needs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        beneficiary_id INTEGER NOT NULL,
        need_title TEXT NOT NULL,
        need_category TEXT DEFAULT 'School Supplies',
        estimated_cost REAL DEFAULT 0.00,
        is_fulfilled INTEGER DEFAULT 0,
        FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS sponsorships (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sponsor_id INTEGER NOT NULL,
        beneficiary_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        frequency TEXT DEFAULT 'monthly',
        start_date TEXT NOT NULL,
        end_date TEXT,
        status TEXT DEFAULT 'active',
        auto_renew INTEGER DEFAULT 1,
        payment_method TEXT DEFAULT 'Mobile Money',
        next_payment_date TEXT,
        total_paid REAL DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sponsorship_id INTEGER,
        sponsor_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        currency TEXT DEFAULT 'UGX',
        payment_method TEXT DEFAULT 'MTN Mobile Money',
        transaction_ref TEXT NOT NULL UNIQUE,
        receipt_number TEXT NOT NULL UNIQUE,
        status TEXT DEFAULT 'completed',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS impact_updates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        beneficiary_id INTEGER NOT NULL,
        org_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        image_url TEXT,
        update_type TEXT DEFAULT 'academic',
        likes_count INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sponsor_id INTEGER NOT NULL,
        beneficiary_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(sponsor_id, beneficiary_id),
        FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        sender_id INTEGER NOT NULL,
        receiver_id INTEGER NOT NULL,
        beneficiary_id INTEGER,
        message_text TEXT NOT NULL,
        attachment_url TEXT,
        attachment_type TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        type TEXT DEFAULT 'update',
        link_url TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ");

    // Insert initial users if empty
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $pwHash = password_hash('sponsor123', PASSWORD_BCRYPT);
        $orgPwHash = password_hash('org123', PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role, phone, avatar_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 'David Kigozi', 'sponsor@example.com', $pwHash, 'sponsor', '+256 701 445 921', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80', 'active']);
        $stmt->execute([2, 'Sarah Namubiru', 'sarah.sponsor@example.com', $pwHash, 'sponsor', '+256 772 334 112', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80', 'active']);
        $stmt->execute([3, 'Noah\'s Ark Children\'s Ministry', 'org@nacmu.org', $orgPwHash, 'organization', '+256 414 550 200', 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?w=150&auto=format&fit=crop&q=80', 'active']);
        $stmt->execute([4, 'NACMU Family Clinic', 'clinic@nacmu.org', $orgPwHash, 'organization', '+256 393 212 900', 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=150&auto=format&fit=crop&q=80', 'active']);

        // Sponsor profiles
        $pdo->exec("INSERT INTO sponsor_profiles (user_id, bio, location, preferred_currency, monthly_budget, anonymous_mode, notification_email, notification_sms) VALUES 
        (1, 'Passionate about educational access and empowering young minds across East Africa.', 'Kampala, Uganda', 'UGX', 450000.00, 0, 1, 1),
        (2, 'Dedicated advocate for healthcare and young girls literacy initiatives.', 'Entebbe, Uganda', 'UGX', 200000.00, 0, 1, 0);");

        // Organizations
        $pdo->exec("INSERT INTO organizations (id, user_id, org_name, org_type, description, location, logo_url, verified, rating, contact_email, contact_phone) VALUES
        (1, 3, 'Noah''s Ark Children''s Ministry Uganda', 'Registered Non-Profit NGO', 'Empowering vulnerable children, youth, and adults in Mukono through holistic primary education, vocational training, and refuge in our children''s home.', 'Mukono, Uganda', 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?w=150&auto=format&fit=crop&q=80', 1, 4.95, 'contact@nacmu.org', '+256 414 550 200'),
        (2, 4, 'NACMU Family Clinic', 'Healthcare Ministry', 'Providing affordable, high-quality healthcare with a special focus on pregnant women, children, and those battling malnutrition in Mukono district.', 'Mukono, Uganda', 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=150&auto=format&fit=crop&q=80', 1, 4.88, 'clinic@nacmu.org', '+256 393 212 900');");

        // Beneficiaries
        $pdo->exec("INSERT INTO beneficiaries (id, org_id, full_name, age, gender, date_of_birth, location, bio, story, photo_url, category, school_grade, dreams, health_status, monthly_need_amount, current_supported_amount, status, urgent_flag) VALUES
        (1, 1, 'Brian Mukisa', 8, 'Male', '2016-04-12', 'Kawempe, Kampala', 'A curious and energetic boy who loves mathematics and building wooden toy cars.', 'Brian lives with his grandmother in Kawempe. His grandmother sells roasted maize, which is often not enough to cover both rent and primary school tuition. Brian consistently scores at the top of his class and dreams of becoming a civil engineer to build better bridges for his community.', 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=600&auto=format&fit=crop&q=80', 'Education', 'Primary 3', 'Civil Engineer', 'Healthy and active', 150000.00, 150000.00, 'fully_sponsored', 0),
        (2, 1, 'Grace Achieng', 10, 'Female', '2014-08-25', 'Katwe, Kampala', 'A compassionate and diligent student with an affinity for storytelling and science.', 'Grace is the eldest of four siblings. Her mother is a seamstress working from home. Sponsorship covers her tuition, nutritious lunch at school, and monthly medical checkups so she can focus on learning without interruption.', 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=600&auto=format&fit=crop&q=80', 'Healthcare & Education', 'Primary 5', 'Medical Doctor', 'Under regular asthma management', 150000.00, 150000.00, 'fully_sponsored', 0),
        (3, 2, 'Daniel Okello', 7, 'Male', '2017-02-18', 'Walukuba, Jinja', 'Enthusiastic reader who loves soccer and drawing native wildlife.', 'Daniel’s family was affected by seasonal river flooding. With your continuous monthly sponsorship, Daniel receives school uniforms, scholastic materials, and warm daily meals at school.', 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?w=600&auto=format&fit=crop&q=80', 'Education', 'Primary 2', 'Pilot', 'Fully vaccinated, healthy', 150000.00, 150000.00, 'fully_sponsored', 0),
        (4, 2, 'Faith Kemigisha', 9, 'Female', '2015-06-10', 'Bweyogere, Wakiso', 'Bright, quiet girl who loves biology, planting trees, and singing in the choir.', 'Faith is raised by her single mother who runs a small vegetable stall. Tuition fees have been challenging this term. Faith needs support for scholastic materials, school meals, and term fees to continue her Primary 4 studies.', 'https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?w=600&auto=format&fit=crop&q=80', 'Education', 'Primary 4', 'Botanist & Teacher', 'Good health', 150000.00, 75000.00, 'partially_sponsored', 1),
        (5, 1, 'Joseph Sserwadda', 11, 'Male', '2013-11-04', 'Kisenyi, Kampala', 'Creative thinker and community helper who excels in English and physical education.', 'Joseph is eager to learn and never misses a day of class. His family struggles with rising costs of textbooks and uniform requirements. Your sponsorship ensures he stays registered for upcoming end-of-year exams.', 'https://images.unsplash.com/photo-1526470608268-f674ce90ebd4?w=600&auto=format&fit=crop&q=80', 'Education', 'Primary 6', 'Computer Scientist', 'Active, good health', 150000.00, 0.00, 'available', 1),
        (6, 2, 'Amina Nakate', 6, 'Female', '2018-09-14', 'Njeru, Buikwe', 'Eager young learner entering primary school with a big smile and love for coloring books.', 'Amina is just beginning her formal schooling journey. Her parents are day laborers seeking consistent financial support for her school books, lunch program, and clean drinking water access.', 'https://images.unsplash.com/photo-1595454223600-91fbdd77e77d?w=600&auto=format&fit=crop&q=80', 'Nutrition & Schooling', 'Primary 1', 'Nurse', 'Normal childhood health', 120000.00, 0.00, 'available', 0),
        (7, 1, 'Samuel Tumusiime', 12, 'Male', '2012-05-30', 'Makindye, Kampala', 'Aspiring athlete and leader among peers with great curiosity for geography.', 'Samuel’s father passed away last year. Samuel takes on daily chores with immense responsibility. Sponsorship provides school supplies and a secure after-school tutoring environment.', 'https://images.unsplash.com/photo-1540479859555-17af45c78602?w=600&auto=format&fit=crop&q=80', 'Leadership & Schooling', 'Primary 7', 'Architect', 'Good health', 160000.00, 0.00, 'available', 0),
        (8, 2, 'Esther Namutebi', 8, 'Female', '2016-07-22', 'Bugembe, Jinja', 'Inquisitive student who enjoys solving puzzles and reciting poems.', 'Esther lives with her aunt. She walks 3 kilometers to school every morning with a smile. Monthly sponsorship covers her transport subsidy, uniforms, and warm breakfast porridge.', 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?w=600&auto=format&fit=crop&q=80', 'Nutrition & Education', 'Primary 3', 'Journalist', 'Healthy', 135000.00, 0.00, 'available', 0);");

        // Needs
        $pdo->exec("INSERT INTO beneficiary_needs (beneficiary_id, need_title, need_category, estimated_cost, is_fulfilled) VALUES
        (1, 'Term 2 School Tuition & Exams', 'Tuition', 85000.00, 1),
        (1, 'Uniform & Black School Shoes', 'Clothing', 35000.00, 1),
        (1, 'Daily Lunch Scheme', 'Nutrition', 30000.00, 1),
        (2, 'Term 2 Tuition & Books', 'Tuition', 90000.00, 1),
        (2, 'Asthma Inhaler & Checkup', 'Healthcare', 35000.00, 1),
        (2, 'School Lunch Program', 'Nutrition', 25000.00, 1),
        (3, 'Primary 2 School Fees', 'Tuition', 80000.00, 1),
        (3, 'Scholastic Stationery Pack', 'Supplies', 40000.00, 1),
        (3, 'School Transport Pass', 'Transport', 30000.00, 1),
        (4, 'Primary 4 Tuition Balance', 'Tuition', 85000.00, 0),
        (4, 'Science & Social Studies Books', 'Books', 40000.00, 0),
        (5, 'Primary 6 Term Registration', 'Tuition', 95000.00, 0),
        (5, 'Math Set & Exercise Books', 'Supplies', 35000.00, 0);");

        // Sponsorships
        $pdo->exec("INSERT INTO sponsorships (id, sponsor_id, beneficiary_id, amount, frequency, start_date, end_date, status, auto_renew, payment_method, next_payment_date, total_paid) VALUES
        (1, 1, 1, 150000.00, 'monthly', '2025-01-15', NULL, 'active', 1, 'MTN Mobile Money', '2026-10-15', 1200000.00),
        (2, 1, 2, 150000.00, 'monthly', '2025-03-10', NULL, 'active', 1, 'Airtel Money', '2026-10-10', 900000.00),
        (3, 1, 3, 150000.00, 'monthly', '2025-06-01', NULL, 'active', 1, 'Visa Card', '2026-10-01', 450000.00);");

        // Transactions
        $pdo->exec("INSERT INTO transactions (id, sponsorship_id, sponsor_id, amount, currency, payment_method, transaction_ref, receipt_number, status, notes, created_at) VALUES
        (1, 1, 1, 150000.00, 'UGX', 'MTN Mobile Money', 'TXN-202609-8831', 'REC-202609-001', 'completed', 'Monthly sponsorship payment for Brian Mukisa (September 2026)', '2026-09-05 09:30:00'),
        (2, 2, 1, 150000.00, 'UGX', 'Airtel Money', 'TXN-202609-7714', 'REC-202609-002', 'completed', 'Monthly sponsorship payment for Grace Achieng (September 2026)', '2026-09-08 14:15:00'),
        (3, 3, 1, 150000.00, 'UGX', 'Visa Card', 'TXN-202609-6623', 'REC-202609-003', 'completed', 'Monthly sponsorship payment for Daniel Okello (September 2026)', '2026-09-01 10:00:00'),
        (4, 1, 1, 150000.00, 'UGX', 'MTN Mobile Money', 'TXN-202608-5541', 'REC-202608-001', 'completed', 'Monthly sponsorship payment for Brian Mukisa (August 2026)', '2026-08-05 09:30:00'),
        (5, 2, 1, 150000.00, 'UGX', 'Airtel Money', 'TXN-202608-4422', 'REC-202608-002', 'completed', 'Monthly sponsorship payment for Grace Achieng (August 2026)', '2026-08-08 14:15:00');");

        // Impact updates
        $pdo->exec("INSERT INTO impact_updates (id, beneficiary_id, org_id, title, content, image_url, update_type, likes_count, created_at) VALUES
        (1, 1, 1, 'Brian scored 94% in End-of-Month Math Exams!', 'Brian has achieved the highest score in Primary 3 mathematics this term! His teacher commended his problem-solving skills and curiosity during fraction exercises. Thank you David for keeping Brian equipped with textbooks and a steady learning routine.', 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=600&auto=format&fit=crop&q=80', 'academic', 14, '2026-09-08 11:20:00'),
        (2, 2, 1, 'Grace received her new science lab notebook and reader', 'Grace was overjoyed to receive her term reader and science project kit today. She has already begun a project on native bird species. Her health checkup at our mobile clinic also confirmed clear lungs and normal vitals.', 'https://images.unsplash.com/photo-1577896851231-70ef18881754?w=600&auto=format&fit=crop&q=80', 'milestone', 21, '2026-09-06 15:45:00'),
        (3, 3, 2, 'Daniel’s handwritten thank-you card to sponsor David', 'Daniel spent art class drawing a passenger airplane and wrote: \"Dear David, thank you for paying my school lunch and fees. I am working hard to fly planes one day!\" We are proud of his discipline and cheerful spirit.', 'https://images.unsplash.com/photo-1588072432836-e10032774350?w=600&auto=format&fit=crop&q=80', 'letter', 38, '2026-09-02 09:10:00'),
        (4, 1, 1, 'Brian completed the Community Tree Planting Day', 'Brian and his classmates planted 15 indigenous trees in the Kawempe school orchard. He named his tree \"Harambee\".', 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=600&auto=format&fit=crop&q=80', 'general', 18, '2026-08-28 16:30:00');");

        // Favorites
        $pdo->exec("INSERT INTO favorites (sponsor_id, beneficiary_id, created_at) VALUES
        (1, 4, '2026-09-04 12:00:00'),
        (1, 5, '2026-09-07 14:20:00');");

        // Messages
        $pdo->exec("INSERT INTO messages (id, sender_id, receiver_id, beneficiary_id, message_text, is_read, created_at) VALUES
        (1, 3, 1, 1, 'Hello David! We are thrilled to share that Brian has received his new term report card. Would you like us to mail the physical copy or send the scanned document?', 1, '2026-09-08 11:30:00'),
        (2, 1, 3, 1, 'Hello Noah''s Ark Children''s Ministry! The scanned copy right here in the app is perfect. Please pass on my warmest congratulations to Brian for his math score!', 1, '2026-09-08 12:05:00'),
        (3, 3, 1, 1, 'Brian was beaming when we told him! He says thank you so much.', 1, '2026-09-08 12:40:00'),
        (4, 4, 1, 3, 'Hello David, Daniel’s quarterly health checkup at the clinic is complete. His attendance has been 100% this quarter!', 0, '2026-09-10 16:00:00');");

        // Notifications
        $pdo->exec("INSERT INTO notifications (id, user_id, title, message, type, link_url, is_read, created_at) VALUES
        (1, 1, 'New Academic Update', 'Brian Mukisa scored 94% in his mathematics exam!', 'update', '/sponsor/updates.php', 0, '2026-09-08 11:25:00'),
        (2, 1, 'New Message from NACMU Family Clinic', 'NACMU Family Clinic sent you an update regarding Daniel Okello.', 'message', '/sponsor/messages.php?org_id=2', 0, '2026-09-10 16:05:00'),
        (3, 1, 'Payment Successful', 'Your monthly contribution of UGX 150,000 for Grace Achieng was processed successfully.', 'payment', '/sponsor/transactions.php', 1, '2026-09-08 14:16:00'),
        (4, 1, 'Thank You Note Received', 'Daniel drew a special thank-you card for your ongoing support.', 'update', '/sponsor/updates.php', 1, '2026-09-02 09:15:00');");
    }

    echo "Database initialized successfully at: " . $dbFile . PHP_EOL;
} catch (Exception $e) {
    echo "Database initialization error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
