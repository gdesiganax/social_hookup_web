USE hookup;

INSERT IGNORE INTO users (id, email, password_hash, role, status, email_verified_at) VALUES
(1, 'admin@hookup.local', '$2y$10$q/HU599.4aH57n6C2SQtXepebE0i8AVNi0GdLtVvjmIZb4WR./xfK', 'admin', 'active', NOW()),
(2, 'alex@example.com', '$2y$10$q/HU599.4aH57n6C2SQtXepebE0i8AVNi0GdLtVvjmIZb4WR./xfK', 'member', 'active', NOW()),
(3, 'mia.c@example.com', '$2y$10$q/HU599.4aH57n6C2SQtXepebE0i8AVNi0GdLtVvjmIZb4WR./xfK', 'member', 'active', NOW()),
(4, 'james.o@example.com', '$2y$10$q/HU599.4aH57n6C2SQtXepebE0i8AVNi0GdLtVvjmIZb4WR./xfK', 'member', 'active', NOW()),
(5, 'taylor.s@example.com', '$2y$10$q/HU599.4aH57n6C2SQtXepebE0i8AVNi0GdLtVvjmIZb4WR./xfK', 'member', 'active', NOW()),
(6, 'jordan.l@example.com', '$2y$10$q/HU599.4aH57n6C2SQtXepebE0i8AVNi0GdLtVvjmIZb4WR./xfK', 'member', 'inactive', NOW()),
(7, 'samira.h@example.com', '$2y$10$q/HU599.4aH57n6C2SQtXepebE0i8AVNi0GdLtVvjmIZb4WR./xfK', 'member', 'active', NOW()),
(8, 'chris.n@example.com', '$2y$10$q/HU599.4aH57n6C2SQtXepebE0i8AVNi0GdLtVvjmIZb4WR./xfK', 'member', 'active', NOW());

INSERT IGNORE INTO profiles (user_id, display_name, username, age, city, gender, looking_for, bio, avatar_url, is_featured) VALUES
(1, 'Site Admin', 'admin', 32, 'Lagos', 'male', 'everyone', 'Platform administrator.', 'https://i.pravatar.cc/150?img=11', 0),
(2, 'Alex Rivera', 'alexriv', 28, 'Brooklyn', 'male', 'female', 'Adventure seeker, coffee enthusiast, and part-time photographer. Looking for spontaneous plans and good conversation.', 'https://i.pravatar.cc/150?img=32', 1),
(3, 'Mia Chen', 'miachen', 26, 'Brooklyn', 'female', 'male', 'Art lover, coffee addict, dog mom. Always looking for a new gallery or a better latte.', 'https://i.pravatar.cc/150?img=1', 1),
(4, 'James Okonkwo', 'james_o', 30, 'Manhattan', 'male', 'female', 'Fitness, travel, live music, and weekend brunch.', 'https://i.pravatar.cc/150?img=5', 1),
(5, 'Taylor Smith', 'taylors', 27, 'Queens', 'female', 'everyone', 'Books, wine, hiking, and slow Sunday mornings.', 'https://i.pravatar.cc/150?img=8', 1),
(6, 'Jordan Lee', 'jordanlee', 29, 'Brooklyn', 'male', 'everyone', 'Guitar, cooking, sunsets, and small music venues.', 'https://i.pravatar.cc/150?img=12', 0),
(7, 'Samira Hassan', 'samira_h', 25, 'Manhattan', 'female', 'male', 'Traveler, foodie, yoga regular, and amateur map collector.', 'https://i.pravatar.cc/150?img=18', 1),
(8, 'Chris Nguyen', 'chrisng', 31, 'Jersey City', 'male', 'female', 'Surf, photography, tacos, and road trips.', 'https://i.pravatar.cc/150?img=15', 0);

INSERT IGNORE INTO interests (id, name) VALUES
(1, 'Photography'),
(2, 'Coffee'),
(3, 'Live music'),
(4, 'Travel'),
(5, 'Hiking'),
(6, 'Art'),
(7, 'Fitness'),
(8, 'Cooking'),
(9, 'Books'),
(10, 'Yoga');

INSERT IGNORE INTO profile_interests (user_id, interest_id) VALUES
(2,1),(2,2),(2,3),(2,4),(2,5),
(3,2),(3,6),(3,9),
(4,3),(4,4),(4,7),
(5,5),(5,9),
(6,3),(6,8),
(7,4),(7,8),(7,10),
(8,1),(8,4),(8,7);

INSERT IGNORE INTO matches (user_one_id, user_two_id, status, matched_at) VALUES
(2,3,'matched',NOW()),
(2,4,'matched',NOW()),
(2,5,'matched',NOW()),
(2,7,'matched',NOW()),
(3,4,'matched',NOW()),
(5,7,'matched',NOW());

INSERT IGNORE INTO conversations (id, type, title, created_by) VALUES
(1, 'direct', NULL, 2),
(2, 'direct', NULL, 2),
(3, 'group', 'Weekend Squad', 2);

INSERT IGNORE INTO conversation_members (conversation_id, user_id) VALUES
(1,2),(1,3),
(2,2),(2,4),
(3,2),(3,3),(3,4),(3,7);

INSERT IGNORE INTO messages (conversation_id, sender_id, body, created_at) VALUES
(1, 3, 'That sounds amazing! Coffee this week?', DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
(1, 2, 'Absolutely. Thursday evening works for me.', DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(2, 4, 'See you Saturday then.', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(3, 7, 'Who is in for Saturday?', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 2, 'I am in. Send the plan.', DATE_SUB(NOW(), INTERVAL 90 MINUTE));

INSERT IGNORE INTO posts (id, user_id, body, created_at) VALUES
(1, 3, 'Looking for someone to explore the city with. Coffee, art, late night talks, and spontaneous plans are all welcome.', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 5, 'Any dog lovers here? I am at the park every weekend. Let us meet up.', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(3, 7, 'Found a tiny restaurant with incredible food and zero wait. This city still knows how to surprise me.', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT IGNORE INTO post_reactions (post_id, user_id, reaction_type) VALUES
(1,2,'like'),(1,4,'like'),(1,5,'like'),(2,2,'like'),(2,3,'like'),(3,2,'like');

INSERT IGNORE INTO post_comments (post_id, user_id, body) VALUES
(1,2,'Count me in.'),
(1,4,'Gallery night sounds good.'),
(2,7,'Dog park dates are elite.');

INSERT IGNORE INTO wallet_accounts (user_id, cash_balance, gift_balance) VALUES
(1, 250000.00, 0.00),
(2, 150000.00, 20000.00),
(3, 100000.00, 15000.00),
(4, 100000.00, 0.00),
(5, 100000.00, 0.00),
(6, 100000.00, 0.00),
(7, 100000.00, 0.00),
(8, 100000.00, 0.00);

INSERT IGNORE INTO gift_catalog (id, name, icon, price, redeemable_amount, is_active) VALUES
(1, 'Coffee Date', 'bi-cup-hot-fill', 2500.00, 2125.00, 1),
(2, 'Rose Bouquet', 'bi-flower1', 7500.00, 6375.00, 1),
(3, 'Dinner Treat', 'bi-egg-fried', 15000.00, 12750.00, 1),
(4, 'Weekend Spark', 'bi-stars', 30000.00, 25500.00, 1);

INSERT IGNORE INTO dates (user_one_id, user_two_id, scheduled_at, location, status) VALUES
(2,3,DATE_ADD(NOW(), INTERVAL 2 DAY),'Blue Note','planned'),
(2,4,DATE_ADD(NOW(), INTERVAL 4 DAY),'Central Park','planned');

INSERT IGNORE INTO reports (reporter_id, reported_user_id, reason, status) VALUES
(3,5,'Suspicious message pattern','open');

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'HookUp'),
('support_email', 'support@hookup.local'),
('default_user_status', 'active'),
('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
