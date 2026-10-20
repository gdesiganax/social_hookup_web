USE hookup;

ALTER TABLE profiles
  ADD COLUMN gender ENUM('female', 'male', 'non_binary') NOT NULL DEFAULT 'female' AFTER city,
  ADD COLUMN looking_for ENUM('female', 'male', 'everyone') NOT NULL DEFAULT 'everyone' AFTER gender,
  ADD INDEX idx_profiles_gender (gender);

UPDATE profiles SET gender = 'male', looking_for = 'everyone' WHERE username = 'admin';
UPDATE profiles SET gender = 'male', looking_for = 'female' WHERE username IN ('alexriv', 'james_o', 'jordanlee', 'chrisng');
UPDATE profiles SET gender = 'female', looking_for = 'male' WHERE username IN ('miachen', 'samira_h');
UPDATE profiles SET gender = 'female', looking_for = 'everyone' WHERE username = 'taylors';
