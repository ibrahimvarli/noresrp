-- NoRestRP Fantasy Role-Playing Database
-- Create database
CREATE DATABASE IF NOT EXISTS fantasy_rp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fantasy_rp;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    avatar VARCHAR(255) DEFAULT 'default.png',
    role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
    theme VARCHAR(20) DEFAULT 'dark',
    language VARCHAR(5) DEFAULT 'en',
    is_online BOOLEAN DEFAULT 0,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_banned BOOLEAN DEFAULT 0,
    remember_token VARCHAR(255) DEFAULT NULL,
    bio TEXT
);

-- Characters table
CREATE TABLE IF NOT EXISTS characters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    race ENUM('human', 'elf', 'dwarf', 'orc') NOT NULL,
    class ENUM('warrior', 'mage', 'rogue', 'cleric') NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    age INT DEFAULT 20,
    birthday DATE DEFAULT NULL,
    height INT DEFAULT 170,
    weight INT DEFAULT 70,
    hair_color VARCHAR(30) DEFAULT NULL,
    hair_style VARCHAR(30) DEFAULT NULL,
    eye_color VARCHAR(30) DEFAULT NULL,
    skin_tone VARCHAR(30) DEFAULT NULL,
    facial_feature VARCHAR(50) DEFAULT NULL,
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    health INT DEFAULT 100,
    max_health INT DEFAULT 100,
    mana INT DEFAULT 50,
    max_mana INT DEFAULT 50,
    energy INT DEFAULT 100,
    max_energy INT DEFAULT 100,
    happiness INT DEFAULT 75,
    max_happiness INT DEFAULT 100,
    strength INT DEFAULT 10,
    dexterity INT DEFAULT 10,
    intelligence INT DEFAULT 10,
    wisdom INT DEFAULT 10,
    charisma INT DEFAULT 10,
    vitality INT DEFAULT 10,
    luck INT DEFAULT 10,
    gold INT DEFAULT 100,
    location_id INT DEFAULT 1,
    portrait VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    backstory TEXT,
    personality_trait_1 VARCHAR(50) DEFAULT NULL,
    personality_trait_2 VARCHAR(50) DEFAULT NULL,
    personality_trait_3 VARCHAR(50) DEFAULT NULL,
    value_1 VARCHAR(50) DEFAULT NULL,
    value_2 VARCHAR(50) DEFAULT NULL,
    flaw VARCHAR(50) DEFAULT NULL,
    last_aging_date DATE DEFAULT NULL,
    current_job_id INT DEFAULT NULL,
    job_title VARCHAR(100) DEFAULT NULL,
    job_level INT DEFAULT 0,
    job_experience INT DEFAULT 0,
    savings INT DEFAULT 0,
    charisma_bonus INT DEFAULT 0,
    social_reputation INT DEFAULT 50,
    relationship_status VARCHAR(50) DEFAULT 'single',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Appearance options
CREATE TABLE IF NOT EXISTS appearance_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('hair_color', 'hair_style', 'eye_color', 'skin_tone', 'facial_feature') NOT NULL,
    race ENUM('human', 'elf', 'dwarf', 'orc', 'all') NOT NULL,
    gender ENUM('male', 'female', 'other', 'all') NOT NULL,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    image VARCHAR(255)
);

-- Personality traits
CREATE TABLE IF NOT EXISTS personality_traits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('trait', 'value', 'flaw') NOT NULL,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    effect TEXT
);

-- Character aging events
CREATE TABLE IF NOT EXISTS character_aging_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    event_type ENUM('birthday', 'attribute_change', 'major_event') NOT NULL,
    event_date DATE NOT NULL,
    description TEXT,
    stat_changes TEXT,
    is_processed BOOLEAN DEFAULT 0,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Character metadata for storing various character settings and states
CREATE TABLE IF NOT EXISTS character_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    key_name VARCHAR(50) NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_character_key (character_id, key_name)
);

-- Items table
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('weapon', 'armor', 'consumable', 'quest', 'misc') NOT NULL,
    subtype VARCHAR(50),
    rarity ENUM('common', 'uncommon', 'rare', 'epic', 'legendary') DEFAULT 'common',
    level_req INT DEFAULT 1,
    value INT DEFAULT 0,
    image VARCHAR(255),
    stat_strength INT DEFAULT 0,
    stat_dexterity INT DEFAULT 0,
    stat_intelligence INT DEFAULT 0,
    stat_wisdom INT DEFAULT 0,
    stat_charisma INT DEFAULT 0,
    damage_min INT DEFAULT 0,
    damage_max INT DEFAULT 0,
    armor INT DEFAULT 0,
    heal_amount INT DEFAULT 0,
    mana_restore INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nutrition_value INT DEFAULT 0,
    hydration_value INT DEFAULT 0,
    treatment_type VARCHAR(50) DEFAULT NULL,
    treatment_power INT DEFAULT 0,
    is_furniture BOOLEAN DEFAULT 0
);

-- Character inventory
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    is_equipped BOOLEAN DEFAULT 0,
    slot VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Locations table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    x_coord INT NOT NULL,
    y_coord INT NOT NULL,
    type ENUM('city', 'dungeon', 'wilderness', 'special') NOT NULL,
    level_req INT DEFAULT 1,
    is_safe BOOLEAN DEFAULT 0,
    parent_id INT DEFAULT NULL,
    FOREIGN KEY (parent_id) REFERENCES locations(id) ON DELETE SET NULL
);

-- Quests table
CREATE TABLE IF NOT EXISTS quests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    level_req INT DEFAULT 1,
    location_id INT,
    reward_exp INT DEFAULT 0,
    reward_gold INT DEFAULT 0,
    reward_item_id INT DEFAULT NULL,
    quest_giver VARCHAR(100),
    is_repeatable BOOLEAN DEFAULT 0,
    is_main_quest BOOLEAN DEFAULT 0,
    prerequisite_quest_id INT DEFAULT NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (reward_item_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (prerequisite_quest_id) REFERENCES quests(id) ON DELETE SET NULL
);

-- Character quests progress
CREATE TABLE IF NOT EXISTS character_quests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    quest_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    progress INT DEFAULT 0,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
);

-- Enemies table
CREATE TABLE IF NOT EXISTS enemies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type VARCHAR(50) NOT NULL,
    level INT DEFAULT 1,
    health INT DEFAULT 100,
    damage_min INT DEFAULT 5,
    damage_max INT DEFAULT 10,
    armor INT DEFAULT 0,
    experience INT DEFAULT 10,
    gold_min INT DEFAULT 1,
    gold_max INT DEFAULT 5,
    image VARCHAR(255),
    location_id INT,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

-- Enemy drops
CREATE TABLE IF NOT EXISTS enemy_drops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enemy_id INT NOT NULL,
    item_id INT NOT NULL,
    drop_chance FLOAT DEFAULT 0.1,
    min_quantity INT DEFAULT 1,
    max_quantity INT DEFAULT 1,
    FOREIGN KEY (enemy_id) REFERENCES enemies(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Battle logs
CREATE TABLE IF NOT EXISTS battle_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    enemy_id INT NOT NULL,
    result ENUM('win', 'loss', 'flee') NOT NULL,
    experience_gained INT DEFAULT 0,
    gold_gained INT DEFAULT 0,
    items_gained TEXT,
    battle_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (enemy_id) REFERENCES enemies(id) ON DELETE CASCADE
);

-- Skills table
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    class_requirement ENUM('warrior', 'mage', 'rogue', 'cleric') DEFAULT NULL,
    skill_type VARCHAR(50) DEFAULT 'general',
    level_req INT DEFAULT 1,
    mana_cost INT DEFAULT 0,
    cooldown INT DEFAULT 0,
    damage_min INT DEFAULT 0,
    damage_max INT DEFAULT 0,
    heal_amount INT DEFAULT 0,
    effect_description TEXT,
    is_professional BOOLEAN DEFAULT 0,
    career_category VARCHAR(50) DEFAULT NULL,
    icon VARCHAR(255)
);

-- Job-specific skills and requirements
CREATE TABLE IF NOT EXISTS job_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    skill_id INT NOT NULL,
    min_level INT DEFAULT 1,
    is_required BOOLEAN DEFAULT 0,
    exp_gain_multiplier FLOAT DEFAULT 1.0,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- Character skills
CREATE TABLE IF NOT EXISTS character_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    skill_id INT NOT NULL,
    level INT DEFAULT 1,
    is_active BOOLEAN DEFAULT 0,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- Forums categories
CREATE TABLE IF NOT EXISTS forum_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    order_num INT DEFAULT 0,
    parent_id INT DEFAULT NULL,
    FOREIGN KEY (parent_id) REFERENCES forum_categories(id) ON DELETE SET NULL
);

-- Forum topics
CREATE TABLE IF NOT EXISTS forum_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    content TEXT,
    is_sticky BOOLEAN DEFAULT 0,
    is_locked BOOLEAN DEFAULT 0,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_post_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Forum posts
CREATE TABLE IF NOT EXISTS forum_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT NOT NULL,
    character_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE SET NULL
);

-- Character life system data
CREATE TABLE IF NOT EXISTS character_life_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    hunger INT DEFAULT 0,
    max_hunger INT DEFAULT 100,
    fatigue INT DEFAULT 0,
    max_fatigue INT DEFAULT 100,
    hygiene INT DEFAULT 100,
    max_hygiene INT DEFAULT 100,
    health INT DEFAULT 100,
    max_health INT DEFAULT 100,
    happiness INT DEFAULT 75,
    max_happiness INT DEFAULT 100,
    mood VARCHAR(50) DEFAULT 'neutral',
    thirst INT DEFAULT 0,
    max_thirst INT DEFAULT 100,
    last_meal TIMESTAMP NULL,
    last_sleep TIMESTAMP NULL,
    last_hygiene TIMESTAMP NULL,
    last_drink TIMESTAMP NULL,
    last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Character diseases
CREATE TABLE IF NOT EXISTS character_diseases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    disease_type VARCHAR(50) NOT NULL,
    disease_name VARCHAR(100) NOT NULL,
    description TEXT,
    severity INT DEFAULT 1,
    duration INT DEFAULT 24, -- Duration in hours
    is_active BOOLEAN DEFAULT 1,
    contracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cured_at TIMESTAMP NULL,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Economy System Tables
-- Jobs available in the game world
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('craftsman', 'merchant', 'adventurer', 'scholar', 'nobility', 'military', 'criminal') NOT NULL,
    level_req INT DEFAULT 1,
    location_id INT,
    base_salary INT DEFAULT 50,
    skill_req VARCHAR(50) DEFAULT NULL,
    skill_level_req INT DEFAULT 0,
    reputation_req INT DEFAULT 0,
    work_hours INT DEFAULT 8,
    exp_gain INT DEFAULT 5,
    max_level INT DEFAULT 10,
    unlock_requirement TEXT,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

-- Character's job history and current job
CREATE TABLE IF NOT EXISTS character_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    job_id INT NOT NULL,
    job_level INT DEFAULT 1,
    experience INT DEFAULT 0,
    hire_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_payment TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    next_payment TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    times_worked INT DEFAULT 0,
    reputation INT DEFAULT 50,
    is_current BOOLEAN DEFAULT 1,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Economy transactions
CREATE TABLE IF NOT EXISTS economy_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    amount INT NOT NULL,
    balance_after INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    related_entity_type VARCHAR(50) DEFAULT NULL,
    related_entity_id INT DEFAULT NULL,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Businesses that can hire characters
CREATE TABLE IF NOT EXISTS businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    owner_type ENUM('npc', 'player', 'system') DEFAULT 'npc',
    owner_id INT DEFAULT NULL,
    location_id INT,
    business_type VARCHAR(50) NOT NULL,
    reputation INT DEFAULT 50,
    income_rate INT DEFAULT 100,
    expenses_rate INT DEFAULT 80,
    available_jobs TEXT,
    level_req INT DEFAULT 1,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

-- Character ownership of businesses
CREATE TABLE IF NOT EXISTS character_businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    business_id INT NOT NULL,
    investment_amount INT DEFAULT 0,
    ownership_percentage INT DEFAULT 100,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_income TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_income INT DEFAULT 0,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Career advancement milestones
CREATE TABLE IF NOT EXISTS career_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    job_level INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    requirement TEXT,
    reward_gold INT DEFAULT 0,
    reward_exp INT DEFAULT 0,
    reward_item_id INT DEFAULT NULL,
    unlock_job_id INT DEFAULT NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_item_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (unlock_job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- Property System Tables
-- Properties available for purchase or rent
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    location_id INT NOT NULL,
    type ENUM('house', 'apartment', 'shop', 'workshop', 'estate', 'farm') NOT NULL,
    size INT NOT NULL COMMENT 'Size in square meters',
    rooms INT DEFAULT 1,
    purchase_price INT DEFAULT 0,
    rent_price INT DEFAULT 0,
    max_furniture INT DEFAULT 10,
    status ENUM('available', 'sold', 'rented', 'unavailable') DEFAULT 'available',
    image VARCHAR(255),
    prestige INT DEFAULT 1 COMMENT 'Affects character status and happiness',
    maintenance_cost INT DEFAULT 5 COMMENT 'Daily upkeep cost',
    is_active BOOLEAN DEFAULT 1,
    is_family_home BOOLEAN DEFAULT 0,
    child_capacity INT DEFAULT 0,
    family_happiness_bonus INT DEFAULT 0,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- Character ownership or rental of properties
CREATE TABLE IF NOT EXISTS character_properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    property_id INT NOT NULL,
    ownership_type ENUM('owned', 'rented') NOT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_payment TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    next_payment TIMESTAMP,
    rent_due INT DEFAULT 0,
    rent_frequency INT DEFAULT 7 COMMENT 'Days between rent payments',
    cleanliness INT DEFAULT 100 COMMENT 'Decreases over time',
    condition_value INT DEFAULT 100 COMMENT 'Structural condition, decreases over time',
    security INT DEFAULT 50 COMMENT 'Protection from theft or damage',
    happiness_bonus INT DEFAULT 5 COMMENT 'Additional happiness for character when at home',
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Furniture and decoration items
CREATE TABLE IF NOT EXISTS furniture (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('bed', 'storage', 'seating', 'table', 'decor', 'lighting', 'kitchen', 'bathroom', 'utility') NOT NULL,
    style ENUM('rustic', 'elegant', 'magical', 'simple', 'exotic', 'royal', 'military') DEFAULT 'simple',
    size INT DEFAULT 1 COMMENT 'Space units required',
    purchase_price INT NOT NULL,
    comfort INT DEFAULT 0 COMMENT 'Affects rest quality',
    aesthetics INT DEFAULT 0 COMMENT 'Affects happiness',
    functionality INT DEFAULT 0 COMMENT 'Practical benefits',
    durability INT DEFAULT 100 COMMENT 'How quickly it wears out',
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT 1
);

-- Furniture placed in properties
CREATE TABLE IF NOT EXISTS property_furniture (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_property_id INT NOT NULL,
    furniture_id INT NOT NULL,
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    rotation INT DEFAULT 0,
    condition_value INT DEFAULT 100,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (character_property_id) REFERENCES character_properties(id) ON DELETE CASCADE,
    FOREIGN KEY (furniture_id) REFERENCES furniture(id) ON DELETE CASCADE
);

-- Property maintenance records
CREATE TABLE IF NOT EXISTS property_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_property_id INT NOT NULL,
    maintenance_type VARCHAR(50) NOT NULL,
    description TEXT,
    cost INT DEFAULT 0,
    scheduled_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_date TIMESTAMP NULL,
    is_emergency BOOLEAN DEFAULT 0,
    status ENUM('pending', 'in_progress', 'completed', 'skipped') DEFAULT 'pending',
    condition_improvement INT DEFAULT 10,
    FOREIGN KEY (character_property_id) REFERENCES character_properties(id) ON DELETE CASCADE
);

-- Property visitors log
CREATE TABLE IF NOT EXISTS property_visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_property_id INT NOT NULL,
    visitor_character_id INT NOT NULL,
    visit_purpose VARCHAR(100),
    visit_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visit_end TIMESTAMP NULL,
    is_authorized BOOLEAN DEFAULT 1,
    FOREIGN KEY (character_property_id) REFERENCES character_properties(id) ON DELETE CASCADE,
    FOREIGN KEY (visitor_character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Social System Tables
-- Character relationships (friendship, romance, marriage)
CREATE TABLE IF NOT EXISTS character_relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    target_character_id INT NOT NULL,
    relationship_type ENUM('friend', 'best_friend', 'dating', 'engaged', 'married', 'ex_spouse', 'parent_child', 'enemy') NOT NULL,
    relationship_level INT DEFAULT 1 COMMENT 'Ranges from 1-100',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_interaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'active', 'declined', 'ended') DEFAULT 'pending',
    is_private BOOLEAN DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (target_character_id) REFERENCES characters(id) ON DELETE CASCADE,
    CONSTRAINT unique_relationship UNIQUE (character_id, target_character_id, relationship_type)
);

-- Relationship history and interactions
CREATE TABLE IF NOT EXISTS relationship_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relationship_id INT NOT NULL,
    interaction_type VARCHAR(50) NOT NULL,
    value_change INT DEFAULT 0 COMMENT 'Positive or negative change to relationship level',
    location_id INT,
    interaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (relationship_id) REFERENCES character_relationships(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);

-- Character marriage data
CREATE TABLE IF NOT EXISTS character_marriages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relationship_id INT NOT NULL,
    marriage_date DATE NOT NULL,
    ceremony_location_id INT,
    property_id INT COMMENT 'Shared property if applicable',
    joint_savings INT DEFAULT 0,
    status ENUM('active', 'separated', 'divorced') DEFAULT 'active',
    divorce_date DATE DEFAULT NULL,
    FOREIGN KEY (relationship_id) REFERENCES character_relationships(id) ON DELETE CASCADE,
    FOREIGN KEY (ceremony_location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
);

-- Children system
CREATE TABLE IF NOT EXISTS character_children (
    id INT AUTO_INCREMENT PRIMARY KEY,
    child_character_id INT NOT NULL,
    parent1_character_id INT NOT NULL,
    parent2_character_id INT,
    birth_date DATE NOT NULL,
    adopted BOOLEAN DEFAULT 0,
    adoption_date DATE DEFAULT NULL,
    is_npc BOOLEAN DEFAULT 0,
    growth_stage ENUM('infant', 'toddler', 'child', 'teen', 'adult') DEFAULT 'infant',
    next_growth_date DATE,
    FOREIGN KEY (child_character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (parent1_character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (parent2_character_id) REFERENCES characters(id) ON DELETE SET NULL
);

-- Character child-raising activities
CREATE TABLE IF NOT EXISTS child_raising_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    child_id INT NOT NULL,
    parent_character_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    benefit VARCHAR(50),
    skill_gain VARCHAR(50),
    activity_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_id) REFERENCES character_children(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Social events system
CREATE TABLE IF NOT EXISTS social_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    host_character_id INT NOT NULL,
    location_id INT NOT NULL,
    property_id INT,
    event_type VARCHAR(50) NOT NULL,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    status ENUM('planned', 'active', 'completed', 'canceled') DEFAULT 'planned',
    is_private BOOLEAN DEFAULT 0,
    max_attendees INT DEFAULT 0,
    FOREIGN KEY (host_character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
);

-- Social event attendees
CREATE TABLE IF NOT EXISTS social_event_attendees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    character_id INT NOT NULL,
    status ENUM('invited', 'attending', 'declined', 'maybe') DEFAULT 'invited',
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_at TIMESTAMP DEFAULT NULL,
    FOREIGN KEY (event_id) REFERENCES social_events(id) ON DELETE CASCADE,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Character private messages
CREATE TABLE IF NOT EXISTS character_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT 0,
    read_at TIMESTAMP DEFAULT NULL,
    is_deleted_by_sender BOOLEAN DEFAULT 0,
    is_deleted_by_receiver BOOLEAN DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES characters(id) ON DELETE CASCADE
);

-- Social interaction points (used for determining relationship progress)
CREATE TABLE IF NOT EXISTS social_interaction_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    relationship_points INT DEFAULT 1,
    cooldown_hours INT DEFAULT 24,
    requires_level INT DEFAULT 1,
    requires_relationship_type VARCHAR(50) DEFAULT NULL,
    requires_relationship_level INT DEFAULT 0,
    category VARCHAR(50) DEFAULT 'general'
);

-- Insert default social interaction types
INSERT INTO social_interaction_types (name, description, relationship_points, cooldown_hours, category) VALUES
('Greet', 'A simple greeting', 1, 4, 'friendly'),
('Small Talk', 'Basic conversation about the weather, news, etc.', 2, 8, 'friendly'),
('Deep Conversation', 'Meaningful dialogue about personal topics', 5, 12, 'friendly'),
('Tell Joke', 'Share a humorous story', 3, 6, 'friendly'),
('Gift', 'Give a present', 8, 24, 'friendly'),
('Help with Task', 'Assist with a job or activity', 6, 12, 'friendly'),
('Share Meal', 'Eat together', 4, 8, 'friendly'),
('Insult', 'Say something offensive', -5, 4, 'negative'),
('Argue', 'Have a disagreement', -8, 12, 'negative'),
('Apologize', 'Say sorry for past actions', 10, 24, 'recovery'),
('Flirt', 'Show romantic interest', 3, 4, 'romantic'),
('Compliment', 'Say something nice', 2, 4, 'friendly'),
('Kiss', 'Share a kiss', 5, 6, 'romantic'),
('Date', 'Go on a romantic outing', 10, 24, 'romantic'),
('Propose', 'Ask for marriage', 0, 48, 'romantic'),
('Care for Child', 'Take care of a child', 3, 6, 'parenting'),
('Teach Skill', 'Teach something new to a child', 5, 12, 'parenting'),
('Family Outing', 'Go somewhere with family', 8, 24, 'family');

-- Insert default event types
INSERT INTO event_types (name, description, min_attendees, reputation_gain, relationship_boost, duration_hours) VALUES
('Dinner Party', 'A formal dinner with multiple courses', 4, 10, 5, 3),
('Birthday Celebration', 'A party to celebrate someone\'s birthday', 3, 8, 4, 4),
('Wedding', 'A ceremony and celebration for marriage', 6, 15, 8, 6),
('Game Night', 'Playing board games or card games together', 2, 5, 4, 3),
('Dance Party', 'A social gathering with dancing', 5, 12, 6, 5),
('Tea Time', 'A small gathering to drink tea and socialize', 2, 3, 3, 2),
('Festival', 'A larger celebration for a special occasion', 8, 15, 5, 8),
('Picnic', 'An outdoor meal', 2, 6, 4, 3),
('Ball', 'A formal dance event', 10, 20, 7, 6);

-- Add infant name generation options
CREATE TABLE IF NOT EXISTS child_names (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    gender ENUM('male', 'female', 'neutral') NOT NULL,
    race ENUM('human', 'elf', 'dwarf', 'orc', 'all') NOT NULL,
    meaning TEXT,
    popularity INT DEFAULT 5 COMMENT 'Range 1-10'
);

-- Insert sample child names
INSERT INTO child_names (name, gender, race, meaning, popularity) VALUES
-- Human names
('James', 'male', 'human', 'Supplanter', 8),
('William', 'male', 'human', 'Resolute protector', 9),
('Oliver', 'male', 'human', 'Olive tree', 7),
('Sophia', 'female', 'human', 'Wisdom', 9),
('Emma', 'female', 'human', 'Universal', 8),
('Olivia', 'female', 'human', 'Olive tree', 9),
('Alex', 'neutral', 'human', 'Defender', 7),
('Jordan', 'neutral', 'human', 'Flowing down', 6),

-- Elf names
('Legolas', 'male', 'elf', 'Green leaf', 7),
('Thranduil', 'male', 'elf', 'Vigorous spring', 6),
('Elrond', 'male', 'elf', 'Star dome', 8),
('Galadriel', 'female', 'elf', 'Maiden crowned with a radiant garland', 8),
('Arwen', 'female', 'elf', 'Noble maiden', 9),
('Tauriel', 'female', 'elf', 'Forest daughter', 7),
('Aerin', 'neutral', 'elf', 'Ocean', 5),

-- Dwarf names
('Thorin', 'male', 'dwarf', 'Bold one', 8),
('Balin', 'male', 'dwarf', 'Powerful', 7),
('Gimli', 'male', 'dwarf', 'Fire', 9),
('Dis', 'female', 'dwarf', 'Lady', 6),
('Dagna', 'female', 'dwarf', 'New day', 5),
('Freya', 'female', 'dwarf', 'Lady', 7),
('Bruni', 'neutral', 'dwarf', 'Brown', 5),

-- Orc names
('Grom', 'male', 'orc', 'Thunder', 8),
('Thrall', 'male', 'orc', 'Slave', 7),
('Durotan', 'male', 'orc', 'Strong home', 6),
('Garona', 'female', 'orc', 'Honored', 7),
('Aggra', 'female', 'orc', 'First attack', 6),
('Draka', 'female', 'orc', 'Dragon', 5),
('Gorka', 'neutral', 'orc', 'Death', 6);

-- Insert some initial data
-- Default admin user (password: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$8zLjnLWHFnNHbAf1QCw6B.OKfO1sRFxIJvEbP6CUcJ0sSveCHKTJy', 'admin@norestrp.com', 'admin');

-- Default locations
INSERT INTO locations (name, description, type, x_coord, y_coord, is_safe) VALUES
('Eldoria', 'The capital city of the realm, a bustling metropolis where adventurers gather.', 'city', 500, 500, 1),
('Silvermist Forest', 'An ancient forest filled with mystical creatures and hidden treasures.', 'wilderness', 600, 450, 0),
('Dwarven Mines', 'Deep underground tunnels rich with precious metals and dangers.', 'dungeon', 400, 550, 0),
('Coastal Haven', 'A peaceful fishing village along the eastern coast.', 'city', 700, 500, 1),
('Dragonfang Mountains', 'Treacherous peaks home to dragons and other fearsome beasts.', 'wilderness', 450, 350, 0);

-- Default items
INSERT INTO items (name, description, type, subtype, rarity, level_req, value, image, nutrition_value, hydration_value) VALUES
('Rusty Sword', 'A basic sword showing signs of rust and wear.', 'weapon', 'sword', 'common', 1, 10, 'rusty_sword.png', 0, 0),
('Wooden Shield', 'A simple wooden shield offering basic protection.', 'armor', 'shield', 'common', 1, 15, 'wooden_shield.png', 0, 0),
('Health Potion', 'A small vial of red liquid that restores health.', 'consumable', 'potion', 'common', 1, 25, 'health_potion.png', 0, 0),
('Apprentice Staff', 'A basic staff for novice mages.', 'weapon', 'staff', 'common', 1, 20, 'apprentice_staff.png', 0, 0),
('Leather Armor', 'Basic leather protection for the torso.', 'armor', 'chest', 'common', 1, 30, 'leather_armor.png', 0, 0),
('Fresh Bread', 'A freshly baked loaf of bread.', 'consumable', 'food', 'common', 1, 5, 'bread.png', 15, 0),
('Apple', 'A juicy, red apple.', 'consumable', 'food', 'common', 1, 2, 'apple.png', 10, 5),
('Cheese Wheel', 'A large wheel of cheese.', 'consumable', 'food', 'common', 1, 8, 'cheese.png', 20, 0),
('Cooked Meat', 'A piece of well-cooked meat.', 'consumable', 'food', 'common', 1, 10, 'meat.png', 25, 0),
('Tavern Meal', 'A hearty tavern meal with meat, vegetables, and ale.', 'consumable', 'food', 'uncommon', 1, 15, 'tavern_meal.png', 40, 5),
('Water Flask', 'A flask of clean water.', 'consumable', 'drink', 'common', 1, 3, 'water_flask.png', 0, 25),
('Ale', 'A mug of locally brewed ale.', 'consumable', 'drink', 'common', 1, 5, 'ale.png', 5, 15),
('Wine', 'A bottle of fine wine.', 'consumable', 'drink', 'uncommon', 1, 12, 'wine.png', 3, 10),
('Fruit Juice', 'Sweet juice made from fresh fruits.', 'consumable', 'drink', 'common', 1, 6, 'fruit_juice.png', 8, 20),
('Milk', 'Fresh milk from local cows.', 'consumable', 'drink', 'common', 1, 4, 'milk.png', 7, 15),
('Tea', 'A soothing herbal tea.', 'consumable', 'drink', 'common', 1, 5, 'tea.png', 2, 18),
('Mead', 'Sweet honey wine.', 'consumable', 'drink', 'uncommon', 1, 8, 'mead.png', 4, 12),
('Healing Herbs', 'Common herbs with minor healing properties.', 'consumable', 'medicine', 'common', 1, 10, 'herbs.png', 0, 0),
('Remedy Potion', 'A potion that cures common ailments.', 'consumable', 'medicine', 'uncommon', 5, 25, 'remedy_potion.png', 0, 0),
('Restoration Elixir', 'A powerful elixir that cures most diseases.', 'consumable', 'medicine', 'rare', 10, 50, 'elixir.png', 0, 0),
('Energy Tonic', 'A tonic that helps recover from exhaustion.', 'consumable', 'medicine', 'uncommon', 3, 20, 'tonic.png', 0, 0),
('Nutritional Supplement', 'A supplement that helps with malnutrition.', 'consumable', 'medicine', 'uncommon', 3, 20, 'supplement.png', 0, 0),
('Soap', 'Basic soap for washing.', 'consumable', 'hygiene', 'common', 1, 5, 'soap.png', 0, 0),
('Perfume', 'A fragrant perfume that helps mask odors.', 'consumable', 'hygiene', 'uncommon', 3, 15, 'perfume.png', 0, 0),
('Luxury Bath Set', 'A set of luxury bath oils and soaps.', 'consumable', 'hygiene', 'rare', 5, 30, 'bath_set.png', 0, 0);

-- Default enemies
INSERT INTO enemies (name, description, type, level, health, damage_min, damage_max, experience, gold_min, gold_max, image, location_id) VALUES
('Wolf', 'A wild wolf roaming the forests looking for prey.', 'beast', 1, 50, 3, 5, 15, 2, 5, 'wolf.png', 2),
('Goblin Scout', 'A sneaky goblin scout armed with a small dagger.', 'humanoid', 2, 65, 4, 7, 20, 3, 7, 'goblin.png', 2),
('Cave Bat', 'A large bat dwelling in the dark depths of caves.', 'beast', 1, 40, 2, 4, 10, 1, 3, 'bat.png', 3),
('Skeletal Warrior', 'The animated remains of a fallen warrior.', 'undead', 3, 80, 5, 9, 30, 5, 10, 'skeleton.png', 3),
('Mountain Bear', 'A ferocious bear protecting its territory.', 'beast', 4, 120, 8, 12, 40, 7, 12, 'bear.png', 5);

-- Default quests
INSERT INTO quests (title, description, level_req, location_id, reward_exp, reward_gold, quest_giver, is_main_quest) VALUES
('Wolf Hunter', 'The local farmers have reported wolves attacking their livestock. Hunt 5 wolves to help them.', 1, 2, 100, 50, 'Farmer Harold', 0),
('Lost Heirloom', 'An elderly woman lost her family heirloom in the Dwarven Mines. Find and return it to her.', 2, 3, 150, 100, 'Elderly Matilda', 0),
('The Ancient Prophecy', 'Discover the truth behind an ancient prophecy that speaks of a hero who will save the realm.', 1, 1, 200, 150, 'Royal Advisor Thalen', 1),
('Fishing Supplies', 'The fishermen of Coastal Haven need new fishing nets. Gather materials to help them.', 1, 4, 80, 40, 'Fisherman Jorin', 0),
('Dragon Threat', 'Reports of a dragon terrorizing the mountain villages have reached the capital. Investigate these claims.', 5, 5, 300, 200, 'Guard Captain Marcus', 1);

-- Forum categories
INSERT INTO forum_categories (name, description, order_num) VALUES
('Announcements', 'Official announcements from the game administrators', 1),
('General Discussion', 'Discuss anything related to the game', 2),
('Character Roleplay', 'In-character discussions and roleplay', 3),
('Guilds & Recruitment', 'Find or advertise your guild', 4),
('Help & Support', 'Get help with game-related issues', 5);

-- Skills
INSERT INTO skills (name, description, class_requirement, level_req, mana_cost, cooldown, damage_min, damage_max, heal_amount, icon) VALUES
('Heroic Strike', 'A powerful strike that deals extra damage to your opponent.', 'warrior', 1, 10, 3, 8, 12, 0, 'heroic_strike.png'),
('Fireball', 'Launches a ball of fire at your target, dealing fire damage.', 'mage', 1, 15, 4, 10, 15, 0, 'fireball.png'),
('Backstab', 'A stealthy attack that does extra damage from behind.', 'rogue', 1, 12, 3, 9, 14, 0, 'backstab.png'),
('Healing Light', 'Bathes an ally in healing light, restoring health.', 'cleric', 1, 20, 5, 0, 0, 25, 'healing_light.png'),
('Shield Bash', 'Bash your opponent with your shield, stunning them briefly.', 'warrior', 3, 15, 6, 5, 8, 0, 'shield_bash.png'),
('Ice Nova', 'Creates an explosion of ice around you, damaging all nearby enemies.', 'mage', 3, 25, 8, 12, 18, 0, 'ice_nova.png'),
('Poison Blade', 'Coats your blade with poison, causing damage over time.', 'rogue', 3, 18, 10, 6, 10, 0, 'poison_blade.png'),
('Holy Shield', 'Creates a protective shield around an ally, absorbing damage.', 'cleric', 3, 22, 12, 0, 0, 15, 'holy_shield.png');

-- Add default appearance options
INSERT INTO appearance_options (category, race, gender, name, description, image) VALUES
-- Hair colors
('hair_color', 'human', 'all', 'Black', 'Deep black hair', 'hair_black.png'),
('hair_color', 'human', 'all', 'Brown', 'Rich brown hair', 'hair_brown.png'),
('hair_color', 'human', 'all', 'Blonde', 'Golden blonde hair', 'hair_blonde.png'),
('hair_color', 'human', 'all', 'Red', 'Fiery red hair', 'hair_red.png'),
('hair_color', 'human', 'all', 'Auburn', 'Reddish-brown hair', 'hair_auburn.png'),
('hair_color', 'elf', 'all', 'Silver', 'Shimmering silver hair', 'hair_silver.png'),
('hair_color', 'elf', 'all', 'Blonde', 'Pale blonde hair', 'hair_elf_blonde.png'),
('hair_color', 'elf', 'all', 'Blue-Black', 'Midnight blue-black hair', 'hair_elf_blueblack.png'),
('hair_color', 'dwarf', 'male', 'Brown', 'Rich brown hair and beard', 'hair_dwarf_brown.png'),
('hair_color', 'dwarf', 'male', 'Red', 'Fiery red hair and beard', 'hair_dwarf_red.png'),
('hair_color', 'dwarf', 'male', 'Black', 'Deep black hair and beard', 'hair_dwarf_black.png'),
('hair_color', 'dwarf', 'female', 'Brown', 'Rich brown hair with braids', 'hair_dwarf_f_brown.png'),
('hair_color', 'dwarf', 'female', 'Red', 'Fiery red hair with braids', 'hair_dwarf_f_red.png'),
('hair_color', 'orc', 'all', 'Black', 'Coarse black hair', 'hair_orc_black.png'),
('hair_color', 'orc', 'all', 'Brown', 'Coarse brown hair', 'hair_orc_brown.png'),

-- Hair styles
('hair_style', 'human', 'male', 'Short', 'Short practical cut', 'style_human_m_short.png'),
('hair_style', 'human', 'male', 'Medium', 'Medium length hair', 'style_human_m_medium.png'),
('hair_style', 'human', 'male', 'Long', 'Long flowing hair', 'style_human_m_long.png'),
('hair_style', 'human', 'female', 'Pixie', 'Short pixie cut', 'style_human_f_pixie.png'),
('hair_style', 'human', 'female', 'Bob', 'Shoulder-length bob', 'style_human_f_bob.png'),
('hair_style', 'human', 'female', 'Long', 'Long flowing hair', 'style_human_f_long.png'),
('hair_style', 'human', 'female', 'Braided', 'Complex braided style', 'style_human_f_braided.png'),
('hair_style', 'elf', 'male', 'Long', 'Long straight hair', 'style_elf_m_long.png'),
('hair_style', 'elf', 'male', 'Braided', 'Elegant braided style', 'style_elf_m_braided.png'),
('hair_style', 'elf', 'female', 'Long', 'Long flowing hair', 'style_elf_f_long.png'),
('hair_style', 'elf', 'female', 'Intricate', 'Intricate elvish style', 'style_elf_f_intricate.png'),
('hair_style', 'dwarf', 'male', 'Braided Beard', 'Elaborately braided beard', 'style_dwarf_m_braided.png'),
('hair_style', 'dwarf', 'male', 'Full Beard', 'Full thick beard', 'style_dwarf_m_full.png'),
('hair_style', 'dwarf', 'female', 'Braided', 'Complex braided style', 'style_dwarf_f_braided.png'),
('hair_style', 'orc', 'male', 'Mohawk', 'Fierce mohawk style', 'style_orc_m_mohawk.png'),
('hair_style', 'orc', 'male', 'Shaved', 'Partially shaved head', 'style_orc_m_shaved.png'),
('hair_style', 'orc', 'female', 'Ponytail', 'Practical high ponytail', 'style_orc_f_ponytail.png'),

-- Eye colors
('eye_color', 'human', 'all', 'Brown', 'Deep brown eyes', 'eyes_brown.png'),
('eye_color', 'human', 'all', 'Blue', 'Ocean blue eyes', 'eyes_blue.png'),
('eye_color', 'human', 'all', 'Green', 'Forest green eyes', 'eyes_green.png'),
('eye_color', 'human', 'all', 'Hazel', 'Warm hazel eyes', 'eyes_hazel.png'),
('eye_color', 'elf', 'all', 'Blue', 'Brilliant blue eyes', 'eyes_elf_blue.png'),
('eye_color', 'elf', 'all', 'Green', 'Emerald green eyes', 'eyes_elf_green.png'),
('eye_color', 'elf', 'all', 'Purple', 'Mysterious purple eyes', 'eyes_elf_purple.png'),
('eye_color', 'dwarf', 'all', 'Brown', 'Deep brown eyes', 'eyes_dwarf_brown.png'),
('eye_color', 'dwarf', 'all', 'Gray', 'Stone gray eyes', 'eyes_dwarf_gray.png'),
('eye_color', 'orc', 'all', 'Red', 'Fierce red eyes', 'eyes_orc_red.png'),
('eye_color', 'orc', 'all', 'Yellow', 'Intense yellow eyes', 'eyes_orc_yellow.png'),
('eye_color', 'orc', 'all', 'Brown', 'Dark brown eyes', 'eyes_orc_brown.png'),

-- Skin tones
('skin_tone', 'human', 'all', 'Fair', 'Light complexion', 'skin_fair.png'),
('skin_tone', 'human', 'all', 'Medium', 'Medium complexion', 'skin_medium.png'),
('skin_tone', 'human', 'all', 'Tan', 'Tan complexion', 'skin_tan.png'),
('skin_tone', 'human', 'all', 'Dark', 'Dark complexion', 'skin_dark.png'),
('skin_tone', 'elf', 'all', 'Pale', 'Pale elvish complexion', 'skin_elf_pale.png'),
('skin_tone', 'elf', 'all', 'Golden', 'Golden elvish complexion', 'skin_elf_golden.png'),
('skin_tone', 'dwarf', 'all', 'Ruddy', 'Ruddy complexion', 'skin_dwarf_ruddy.png'),
('skin_tone', 'dwarf', 'all', 'Tan', 'Work-hardened tan', 'skin_dwarf_tan.png'),
('skin_tone', 'orc', 'all', 'Green', 'Traditional green orcish skin', 'skin_orc_green.png'),
('skin_tone', 'orc', 'all', 'Gray', 'Ashen gray orcish skin', 'skin_orc_gray.png'),

-- Facial features
('facial_feature', 'human', 'all', 'Scar', 'A distinctive facial scar', 'face_scar.png'),
('facial_feature', 'human', 'all', 'Freckles', 'Light freckles across the face', 'face_freckles.png'),
('facial_feature', 'human', 'male', 'Beard', 'Full beard', 'face_beard.png'),
('facial_feature', 'human', 'male', 'Goatee', 'Neat goatee', 'face_goatee.png'),
('facial_feature', 'elf', 'all', 'Tattoo', 'Mystical facial tattoo', 'face_elf_tattoo.png'),
('facial_feature', 'dwarf', 'male', 'Braided Beard', 'Elaborately braided beard', 'face_dwarf_braided.png'),
('facial_feature', 'orc', 'all', 'Tusks', 'Prominent tusks', 'face_orc_tusks.png'),
('facial_feature', 'orc', 'all', 'War Paint', 'Ritual war paint', 'face_orc_paint.png');

-- Add default personality traits
INSERT INTO personality_traits (type, name, description, effect) VALUES
-- Personality traits
('trait', 'Brave', 'Faces danger without fear', 'Bonus to will saves and fear resistance'),
('trait', 'Cautious', 'Thinks before acting', 'Bonus to perception but slower reaction time'),
('trait', 'Charming', 'Naturally likable and persuasive', 'Bonus to social interactions with NPCs'),
('trait', 'Curious', 'Always seeking new knowledge', 'Learns skills faster but may get into trouble'),
('trait', 'Disciplined', 'Highly focused and controlled', 'Bonus to concentration tasks'),
('trait', 'Generous', 'Willing to share with others', 'Better NPC reactions but less money'),
('trait', 'Hot-headed', 'Quick to anger', 'Combat bonus when injured but less diplomatic'),
('trait', 'Humorous', 'Always ready with a joke', 'Can improve NPC mood but may not be taken seriously'),
('trait', 'Insightful', 'Sees beyond the obvious', 'Bonus to perception and investigation'),
('trait', 'Loyal', 'Steadfast and dependable to allies', 'Bonus when helping allies'),
('trait', 'Mysterious', 'Keeps to themself', 'NPCs may be intrigued but less likely to share information'),
('trait', 'Observant', 'Notices small details', 'Bonus to finding hidden objects and traps'),
('trait', 'Patient', 'Willing to wait for the right moment', 'Bonus to long-term tasks'),
('trait', 'Proud', 'Strong sense of self-worth', 'Resistant to manipulation but may refuse help'),
('trait', 'Reckless', 'Takes unnecessary risks', 'Combat bonus but more likely to be injured'),
('trait', 'Suspicious', 'Trusts no one', 'Less likely to be tricked but has trouble forming relationships'),
('trait', 'Thoughtful', 'Considers all angles', 'Better decision making but slower to act'),
('trait', 'Vengeful', 'Never forgets a slight', 'Bonus against enemies who have harmed you'),

-- Values
('value', 'Honor', 'Lives by a strict code', 'Respected by similar NPCs but inflexible'),
('value', 'Freedom', 'Values personal liberty above all', 'Resistant to control but may clash with authority'),
('value', 'Knowledge', 'Seeks understanding of the world', 'Learns faster but may be distracted by new information'),
('value', 'Wealth', 'Desires material possessions', 'Better at haggling but may be seen as greedy'),
('value', 'Power', 'Seeks to control their own destiny', 'Ambitious but may make enemies'),
('value', 'Family', 'Puts loved ones first', 'Strong bonds but vulnerable through attachments'),
('value', 'Nature', 'Respects the natural world', 'Bonus in wilderness but uncomfortable in cities'),
('value', 'Justice', 'Believes in fairness and law', 'Respected by authorities but rigid'),
('value', 'Tradition', 'Honors the old ways', 'Strong cultural connections but resistant to change'),
('value', 'Faith', 'Devoted to higher power', 'Spiritual strength but may be seen as zealous'),

-- Flaws
('flaw', 'Arrogant', 'Overestimates own abilities', 'Penalty to teamwork'),
('flaw', 'Greedy', 'Always wants more', 'May make poor decisions for wealth'),
('flaw', 'Cowardly', 'Avoids danger at all costs', 'May flee from combat'),
('flaw', 'Deceitful', 'Comfortable with lying', 'Better at deception but less trusted'),
('flaw', 'Impulsive', 'Acts without thinking', 'Quick to act but poor planning'),
('flaw', 'Jealous', 'Envies others' possessions or achievements', 'May sabotage allies'),
('flaw', 'Lazy', 'Avoids unnecessary effort', 'Energy regenerates faster but skills improve slower'),
('flaw', 'Selfish', 'Puts own needs first', 'Makes advantageous deals but poor ally'),
('flaw', 'Stubborn', 'Refuses to change mind', 'Resistant to manipulation but won't adapt'),
('flaw', 'Vain', 'Obsessed with appearance', 'Bonus to first impressions but easily insulted');

-- Insert basic jobs
INSERT INTO jobs (name, description, category, level_req, location_id, base_salary, work_hours, exp_gain, max_level, image) VALUES
('Apprentice Blacksmith', 'Learn the basics of blacksmithing by assisting at a forge.', 'craftsman', 1, 1, 30, 8, 5, 5, 'blacksmith.png'),
('Blacksmith', 'Craft weapons and armor at a forge.', 'craftsman', 5, 1, 60, 8, 8, 10, 'blacksmith.png'),
('Master Blacksmith', 'Craft masterwork weapons and armor.', 'craftsman', 10, 1, 120, 8, 12, 15, 'blacksmith.png'),

('Herb Gatherer', 'Collect herbs from the wilderness for alchemists and healers.', 'adventurer', 1, 2, 25, 6, 4, 5, 'herb_gatherer.png'),
('Apprentice Alchemist', 'Mix basic potions and learn alchemy.', 'craftsman', 3, 1, 40, 8, 6, 5, 'alchemist.png'),
('Alchemist', 'Create potions and elixirs for various purposes.', 'craftsman', 7, 1, 80, 8, 10, 10, 'alchemist.png'),

('Market Vendor', 'Sell goods at the market for a merchant.', 'merchant', 1, 1, 35, 10, 4, 5, 'vendor.png'),
('Merchant', 'Trade goods between cities for profit.', 'merchant', 5, 1, 70, 10, 8, 10, 'merchant.png'),
('Master Merchant', 'Run a large trading enterprise across multiple cities.', 'merchant', 10, 1, 150, 10, 12, 15, 'merchant.png'),

('Guard', 'Protect the city gates and maintain order.', 'military', 3, 1, 50, 12, 7, 5, 'guard.png'),
('City Watch Officer', 'Lead a squad of guards and enforce city laws.', 'military', 8, 1, 90, 12, 10, 10, 'guard_captain.png'),
('Guard Captain', 'Command the city watch and ensure the safety of citizens.', 'military', 15, 1, 180, 12, 15, 15, 'guard_captain.png'),

('Tavern Helper', 'Clean tables, serve drinks, and assist at the tavern.', 'craftsman', 1, 1, 20, 6, 3, 3, 'tavern_helper.png'),
('Bartender', 'Mix drinks and manage a bar counter.', 'craftsman', 3, 1, 45, 8, 5, 5, 'bartender.png'),
('Tavern Owner', 'Run a tavern, hire staff, and maximize profits.', 'merchant', 10, 1, 100, 12, 10, 10, 'tavern_owner.png'),

('Courier', 'Deliver messages and small packages across the city.', 'adventurer', 1, 1, 30, 5, 5, 3, 'courier.png'),
('Scout', 'Explore dangerous areas and report back on threats.', 'adventurer', 5, 2, 65, 6, 10, 8, 'scout.png'),
('Explorer', 'Chart unknown territories and document discoveries.', 'adventurer', 10, 5, 120, 8, 15, 15, 'explorer.png'),

('Apprentice Mage', 'Study basic magic at the academy.', 'scholar', 3, 1, 40, 8, 8, 5, 'apprentice_mage.png'),
('Mage', 'Research and practice advanced spells.', 'scholar', 8, 1, 85, 8, 12, 10, 'mage.png'),
('Archmage', 'Master the highest forms of magic and lead magical research.', 'scholar', 15, 1, 200, 8, 20, 20, 'archmage.png'),

('Farmhand', 'Work on a farm tending to crops and animals.', 'craftsman', 1, 4, 25, 10, 3, 3, 'farmhand.png'),
('Farmer', 'Grow crops and raise animals on your own plot of land.', 'craftsman', 5, 4, 50, 10, 5, 8, 'farmer.png'),
('Estate Manager', 'Oversee a large agricultural estate with multiple farms.', 'nobility', 10, 4, 120, 10, 10, 15, 'estate_manager.png'),

('Hunter', 'Hunt wild game and sell meat and hides.', 'adventurer', 2, 2, 45, 6, 8, 8, 'hunter.png'),
('Fisherman', 'Catch fish from lakes, rivers, or the sea.', 'craftsman', 1, 4, 35, 8, 4, 8, 'fisherman.png'),
('Miner', 'Extract valuable ores and minerals from the mines.', 'craftsman', 2, 3, 55, 10, 6, 10, 'miner.png');

-- Insert basic businesses
INSERT INTO businesses (name, description, owner_type, location_id, business_type, available_jobs, level_req, image) VALUES
('The Anvil & Hammer', 'A busy blacksmith shop producing weapons and armor for adventurers.', 'npc', 1, 'blacksmith', '1,2,3', 1, 'blacksmith_shop.png'),
('Elixirs & Remedies', 'An alchemy shop selling potions and remedies of all kinds.', 'npc', 1, 'alchemy_shop', '5,6', 1, 'alchemy_shop.png'),
('The Golden Coin', 'A trading company dealing in goods from across the realm.', 'npc', 1, 'trading_company', '7,8,9', 1, 'trading_company.png'),
('City Watch Headquarters', 'The base of operations for the city\'s guards and protectors.', 'system', 1, 'guard_post', '10,11,12', 1, 'guard_post.png'),
('The Drunken Dragon', 'A popular tavern frequented by adventurers and locals alike.', 'npc', 1, 'tavern', '13,14,15', 1, 'tavern.png'),
('Swift Message Service', 'A courier service delivering messages throughout the realm.', 'npc', 1, 'courier_service', '16', 1, 'courier_service.png'),
('Arcane Academy', 'A prestigious institution for the study of magic.', 'system', 1, 'magic_academy', '17,18,19', 3, 'magic_academy.png'),
('Green Meadows Farm', 'A productive farm growing various crops and raising livestock.', 'npc', 4, 'farm', '20,21', 1, 'farm.png'),
('Huntsman\'s Lodge', 'A gathering place for hunters who track and hunt wild game.', 'npc', 2, 'hunting_lodge', '22', 1, 'hunting_lodge.png'),
('Coastal Fishery', 'A busy fishery bringing in fresh catches daily.', 'npc', 4, 'fishery', '23', 1, 'fishery.png'),
('Deep Delve Mining Company', 'A mining operation extracting valuable ores from the mountain.', 'npc', 3, 'mining_company', '24', 1, 'mining_company.png');

-- Insert career milestones
INSERT INTO career_milestones (job_id, job_level, title, description, requirement, reward_gold, reward_exp, unlock_job_id) VALUES
(1, 3, 'Skilled Apprentice', 'You have shown aptitude in basic blacksmithing techniques.', 'Work as an Apprentice Blacksmith for 20 days', 100, 50, NULL),
(1, 5, 'Ready for Advancement', 'You have mastered the basics and are ready to become a full Blacksmith.', 'Reach level 5 as an Apprentice Blacksmith', 300, 100, 2),
(2, 5, 'Respected Smith', 'Your craftsmanship is recognized throughout the city.', 'Craft 50 items as a Blacksmith', 500, 150, NULL),
(2, 10, 'Master Candidate', 'You have developed unique techniques and are ready for mastery.', 'Reach level 10 as a Blacksmith', 1000, 300, 3),

(5, 3, 'Potion Mixer', 'You can now create basic potions without supervision.', 'Successfully create 30 basic potions', 120, 60, NULL),
(5, 5, 'Ready for Advancement', 'You have proven your knowledge of alchemy fundamentals.', 'Reach level 5 as an Apprentice Alchemist', 250, 100, 6),
(6, 5, 'Formula Innovator', 'You have begun developing your own unique potion formulas.', 'Create 20 advanced potions', 400, 150, NULL),

(7, 3, 'Bargainer', 'You have learned to negotiate better prices with customers.', 'Complete 40 sales transactions', 150, 50, NULL),
(7, 5, 'Ready for Advancement', 'You understand the basics of trade and commerce.', 'Reach level 5 as a Market Vendor', 300, 100, 8),
(8, 5, 'Trade Network', 'You have established a network of reliable suppliers and customers.', 'Trade with merchants in 3 different cities', 600, 200, NULL),
(8, 10, 'Commercial Magnate', 'Your business acumen has attracted attention from wealthy investors.', 'Reach level 10 as a Merchant', 1200, 300, 9);

-- Insert sample properties
INSERT INTO properties (name, description, location_id, type, size, rooms, purchase_price, rent_price, max_furniture, status, image, prestige, maintenance_cost) VALUES
('Cozy Cottage', 'A small but comfortable cottage near the town square.', 1, 'house', 75, 2, 5000, 50, 15, 'available', 'cottage.png', 2, 10),
('Merchant Apartment', 'A modest apartment above the market district.', 1, 'apartment', 60, 1, 3500, 35, 10, 'available', 'apartment.png', 1, 7),
('Riverside Home', 'A charming house with a view of the river.', 1, 'house', 100, 3, 8000, 80, 20, 'available', 'riverside_home.png', 3, 15),
('Blacksmith Workshop', 'A functional workshop with living quarters attached.', 1, 'workshop', 120, 2, 12000, 100, 25, 'available', 'workshop.png', 2, 20),
('Noble Estate', 'A luxurious estate with gardens and servant quarters.', 1, 'estate', 300, 8, 50000, 400, 60, 'available', 'estate.png', 8, 50),
('Farm Homestead', 'A productive farm with a comfortable farmhouse.', 4, 'farm', 500, 4, 25000, 200, 30, 'available', 'farm.png', 4, 35),
('Forest Cabin', 'A secluded cabin on the edge of Silvermist Forest.', 2, 'house', 65, 1, 4000, 40, 12, 'available', 'cabin.png', 1, 8),
('Mining Quarters', 'Simple living quarters near the Dwarven Mines.', 3, 'apartment', 50, 1, 2500, 25, 8, 'available', 'mining_quarters.png', 1, 5),
('Coastal Villa', 'A beautiful villa overlooking the sea in Coastal Haven.', 4, 'house', 150, 4, 20000, 160, 30, 'available', 'villa.png', 6, 25),
('Mountain Retreat', 'A reinforced home high in the Dragonfang Mountains.', 5, 'house', 90, 2, 15000, 120, 18, 'available', 'mountain_home.png', 5, 20);

-- Insert sample furniture
INSERT INTO furniture (name, description, type, style, size, purchase_price, comfort, aesthetics, functionality, durability, image) VALUES
('Simple Bed', 'A basic wooden bed with a straw mattress.', 'bed', 'simple', 4, 200, 3, 1, 5, 80, 'simple_bed.png'),
('Comfortable Bed', 'A well-crafted bed with a wool mattress.', 'bed', 'rustic', 4, 500, 6, 3, 6, 90, 'comfortable_bed.png'),
('Luxurious Bed', 'A beautiful four-poster bed with silk sheets.', 'bed', 'elegant', 6, 2000, 10, 8, 7, 95, 'luxurious_bed.png'),
('Enchanted Bed', 'A bed enchanted to provide restful sleep.', 'bed', 'magical', 4, 3500, 15, 7, 9, 100, 'enchanted_bed.png'),

('Wooden Chest', 'A simple chest for storing items.', 'storage', 'simple', 2, 100, 0, 1, 5, 75, 'wooden_chest.png'),
('Elegant Wardrobe', 'A beautifully carved wardrobe for clothing.', 'storage', 'elegant', 3, 800, 0, 7, 6, 85, 'wardrobe.png'),
('Magical Bookcase', 'A bookcase that can hold more books than it appears.', 'storage', 'magical', 3, 1500, 0, 6, 9, 90, 'magical_bookcase.png'),

('Wooden Chair', 'A simple wooden chair.', 'seating', 'simple', 1, 50, 2, 1, 4, 70, 'wooden_chair.png'),
('Padded Armchair', 'A comfortable armchair with cushions.', 'seating', 'rustic', 2, 300, 7, 4, 5, 80, 'armchair.png'),
('Royal Throne', 'An impressive throne fit for nobility.', 'seating', 'royal', 3, 3000, 6, 10, 3, 95, 'throne.png'),

('Simple Table', 'A basic wooden table.', 'table', 'simple', 2, 100, 0, 1, 6, 75, 'simple_table.png'),
('Dining Table', 'A large table for sharing meals.', 'table', 'rustic', 4, 400, 0, 3, 8, 80, 'dining_table.png'),
('Enchanted Desk', 'A desk that organizes your papers for you.', 'table', 'magical', 3, 1200, 0, 5, 10, 90, 'magical_desk.png'),

('Wall Torch', 'A simple torch held in a wall bracket.', 'lighting', 'simple', 1, 30, 0, 1, 5, 60, 'wall_torch.png'),
('Candelabra', 'An elegant multi-candle holder.', 'lighting', 'elegant', 1, 200, 0, 6, 5, 70, 'candelabra.png'),
('Everburning Lamp', 'A magical lamp that never needs refueling.', 'lighting', 'magical', 1, 800, 0, 5, 10, 100, 'magical_lamp.png'),

('Simple Rug', 'A basic woven rug.', 'decor', 'simple', 2, 80, 1, 2, 2, 60, 'simple_rug.png'),
('Elegant Tapestry', 'A beautifully woven wall hanging depicting a story.', 'decor', 'elegant', 2, 600, 0, 8, 1, 85, 'tapestry.png'),
('Enchanted Painting', 'A painting with moving figures inside.', 'decor', 'magical', 1, 1500, 0, 9, 3, 95, 'magical_painting.png'),

('Simple Stove', 'A basic cooking stove.', 'kitchen', 'simple', 2, 300, 0, 1, 7, 80, 'simple_stove.png'),
('Cooking Range', 'A high-quality stove for serious cooking.', 'kitchen', 'rustic', 3, 900, 0, 3, 9, 85, 'cooking_range.png'),
('Magical Cauldron', 'A cauldron that speeds up cooking and brewing.', 'kitchen', 'magical', 2, 2000, 0, 4, 10, 95, 'magical_cauldron.png'),

('Washing Basin', 'A simple basin for washing.', 'bathroom', 'simple', 1, 150, 0, 1, 6, 70, 'washing_basin.png'),
('Bathtub', 'A copper bathtub for bathing.', 'bathroom', 'rustic', 3, 700, 5, 3, 7, 80, 'bathtub.png'),
('Enchanted Bath', 'A magical bath that heats and cleans the water.', 'bathroom', 'magical', 3, 3000, 8, 7, 10, 90, 'enchanted_bath.png'),

('Simple Shelf', 'A basic wooden shelf for display.', 'utility', 'simple', 1, 60, 0, 1, 5, 70, 'simple_shelf.png'),
('Tool Rack', 'A rack for organizing tools.', 'utility', 'rustic', 2, 200, 0, 2, 8, 80, 'tool_rack.png'),
('Alchemy Stand', 'A specialized stand for potion making.', 'utility', 'magical', 2, 1000, 0, 3, 10, 85, 'alchemy_stand.png');

-- Insert social event types
INSERT INTO event_types (name, description, min_attendees, reputation_gain, relationship_boost, duration_hours) VALUES
('Dinner Party', 'A formal dinner with multiple courses', 4, 10, 5, 3),
('Birthday Celebration', 'A party to celebrate someone\'s birthday', 3, 8, 4, 4),
('Wedding', 'A ceremony and celebration for marriage', 6, 15, 8, 6),
('Game Night', 'Playing board games or card games together', 2, 5, 4, 3),
('Dance Party', 'A social gathering with dancing', 5, 12, 6, 5),
('Tea Time', 'A small gathering to drink tea and socialize', 2, 3, 3, 2),
('Festival', 'A larger celebration for a special occasion', 8, 15, 5, 8),
('Picnic', 'An outdoor meal', 2, 6, 4, 3),
('Ball', 'A formal dance event', 10, 20, 7, 6);

-- Insert sample child names
INSERT INTO child_names (name, gender, race, meaning, popularity) VALUES
-- Human names
('James', 'male', 'human', 'Supplanter', 8),
('William', 'male', 'human', 'Resolute protector', 9),
('Oliver', 'male', 'human', 'Olive tree', 7),
('Sophia', 'female', 'human', 'Wisdom', 9),
('Emma', 'female', 'human', 'Universal', 8),
('Olivia', 'female', 'human', 'Olive tree', 9),
('Alex', 'neutral', 'human', 'Defender', 7),
('Jordan', 'neutral', 'human', 'Flowing down', 6),

-- Elf names
('Legolas', 'male', 'elf', 'Green leaf', 7),
('Thranduil', 'male', 'elf', 'Vigorous spring', 6),
('Elrond', 'male', 'elf', 'Star dome', 8),
('Galadriel', 'female', 'elf', 'Maiden crowned with a radiant garland', 8),
('Arwen', 'female', 'elf', 'Noble maiden', 9),
('Tauriel', 'female', 'elf', 'Forest daughter', 7),
('Aerin', 'neutral', 'elf', 'Ocean', 5),

-- Dwarf names
('Thorin', 'male', 'dwarf', 'Bold one', 8),
('Balin', 'male', 'dwarf', 'Powerful', 7),
('Gimli', 'male', 'dwarf', 'Fire', 9),
('Dis', 'female', 'dwarf', 'Lady', 6),
('Dagna', 'female', 'dwarf', 'New day', 5),
('Freya', 'female', 'dwarf', 'Lady', 7),
('Bruni', 'neutral', 'dwarf', 'Brown', 5),

-- Orc names
('Grom', 'male', 'orc', 'Thunder', 8),
('Thrall', 'male', 'orc', 'Slave', 7),
('Durotan', 'male', 'orc', 'Strong home', 6),
('Garona', 'female', 'orc', 'Honored', 7),
('Aggra', 'female', 'orc', 'First attack', 6),
('Draka', 'female', 'orc', 'Dragon', 5),
('Gorka', 'neutral', 'orc', 'Death', 6);

-- World and Environment System Tables
-- Districts/neighborhoods within locations
CREATE TABLE IF NOT EXISTS districts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('residential', 'commercial', 'industrial', 'entertainment', 'government', 'slums', 'noble', 'market', 'military', 'magical') NOT NULL,
    safety_level INT DEFAULT 50 COMMENT 'From 0 (dangerous) to 100 (safe)',
    wealth_level INT DEFAULT 50 COMMENT 'From 0 (poor) to 100 (wealthy)',
    population INT DEFAULT 1000,
    tax_rate FLOAT DEFAULT 0.1,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- Points of interest in districts
CREATE TABLE IF NOT EXISTS points_of_interest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    district_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('shop', 'restaurant', 'tavern', 'park', 'temple', 'marketplace', 'guild', 'library', 'barracks', 'entertainment', 'other') NOT NULL,
    subtype VARCHAR(50) DEFAULT NULL,
    owner_type ENUM('npc', 'player', 'system') DEFAULT 'npc',
    owner_id INT DEFAULT NULL,
    quality INT DEFAULT 50 COMMENT 'From 0 (poor) to 100 (luxurious)',
    popularity INT DEFAULT 50 COMMENT 'From 0 (empty) to 100 (crowded)',
    open_hour INT DEFAULT 8 COMMENT 'Hour of day when POI opens (0-23)',
    close_hour INT DEFAULT 20 COMMENT 'Hour of day when POI closes (0-23)',
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE
);

-- Menu items for restaurants/taverns
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poi_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('food', 'drink', 'special') NOT NULL,
    price INT NOT NULL,
    quality INT DEFAULT 50,
    health_restore INT DEFAULT 0,
    happiness_bonus INT DEFAULT 0,
    effects TEXT COMMENT 'Special effects like buffs',
    image VARCHAR(255),
    is_seasonal BOOLEAN DEFAULT 0,
    season ENUM('spring', 'summer', 'autumn', 'winter', 'all') DEFAULT 'all',
    FOREIGN KEY (poi_id) REFERENCES points_of_interest(id) ON DELETE CASCADE
);

-- Shops inventory
CREATE TABLE IF NOT EXISTS shop_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poi_id INT NOT NULL,
    item_id INT NOT NULL,
    stock_quantity INT DEFAULT 10,
    price_modifier FLOAT DEFAULT 1.0 COMMENT 'Multiplier for base item price',
    is_featured BOOLEAN DEFAULT 0,
    restock_days INT DEFAULT 7 COMMENT 'Days until restocking',
    last_restock TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poi_id) REFERENCES points_of_interest(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Park activities
CREATE TABLE IF NOT EXISTS park_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poi_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    happiness_gain INT DEFAULT 5,
    energy_cost INT DEFAULT 10,
    duration_minutes INT DEFAULT 60,
    max_participants INT DEFAULT 0 COMMENT '0 means unlimited',
    required_weather ENUM('any', 'sunny', 'cloudy', 'rainy', 'snowy') DEFAULT 'any',
    required_time ENUM('any', 'day', 'night') DEFAULT 'any',
    FOREIGN KEY (poi_id) REFERENCES points_of_interest(id) ON DELETE CASCADE
);

-- Weather system
CREATE TABLE IF NOT EXISTS weather_system (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    current_weather ENUM('sunny', 'cloudy', 'rainy', 'stormy', 'snowy', 'foggy', 'windy') DEFAULT 'sunny',
    current_temperature INT DEFAULT 20 COMMENT 'In Celsius',
    weather_intensity INT DEFAULT 50 COMMENT 'From 0 (mild) to 100 (severe)',
    forecast TEXT COMMENT 'JSON array with forecast for next days',
    weather_effects TEXT COMMENT 'Effects on gameplay',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    next_change TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- Seasons configuration
CREATE TABLE IF NOT EXISTS seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name ENUM('spring', 'summer', 'autumn', 'winter') NOT NULL,
    display_name VARCHAR(50) NOT NULL,
    description TEXT,
    start_day INT NOT NULL COMMENT 'Day of year when season starts (1-365)',
    end_day INT NOT NULL COMMENT 'Day of year when season ends (1-365)',
    base_temperature INT DEFAULT 20,
    temperature_variation INT DEFAULT 5,
    day_length_hours FLOAT DEFAULT 12.0,
    weather_probabilities TEXT COMMENT 'JSON with weather type probabilities',
    season_effects TEXT COMMENT 'Effects on gameplay',
    image VARCHAR(255)
);

-- Day-night cycle
CREATE TABLE IF NOT EXISTS game_time (
    id INT AUTO_INCREMENT PRIMARY KEY,
    current_date DATE NOT NULL,
    current_time TIME NOT NULL,
    day_of_year INT NOT NULL COMMENT 'Current day of year (1-365)',
    current_season VARCHAR(20) NOT NULL,
    sunrise_time TIME NOT NULL,
    sunset_time TIME NOT NULL,
    is_day_time BOOLEAN DEFAULT 1,
    time_speed FLOAT DEFAULT 1.0 COMMENT 'How fast time passes compared to real time',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Time-based events
CREATE TABLE IF NOT EXISTS time_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    event_type ENUM('daily', 'seasonal', 'special') NOT NULL,
    trigger_time TIME DEFAULT NULL,
    trigger_season VARCHAR(20) DEFAULT NULL,
    trigger_date DATE DEFAULT NULL,
    duration_minutes INT DEFAULT 60,
    location_id INT DEFAULT NULL,
    district_id INT DEFAULT NULL,
    effects TEXT COMMENT 'Effects on gameplay',
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL
);

-- Character location tracking with time
CREATE TABLE IF NOT EXISTS character_location_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    character_id INT NOT NULL,
    location_id INT NOT NULL,
    district_id INT DEFAULT NULL,
    poi_id INT DEFAULT NULL,
    arrival_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    departure_time TIMESTAMP DEFAULT NULL,
    FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (poi_id) REFERENCES points_of_interest(id) ON DELETE SET NULL
);

-- Insert default seasons
INSERT INTO seasons (name, display_name, description, start_day, end_day, base_temperature, temperature_variation, day_length_hours, weather_probabilities, image) VALUES
('spring', 'Spring', 'A time of renewal and growth, with blooming flowers and mild weather.', 1, 91, 15, 7, 12.5, '{"sunny": 0.5, "cloudy": 0.3, "rainy": 0.2, "foggy": 0.1, "windy": 0.1}', 'spring.png'),
('summer', 'Summer', 'The warmest season with long days and short nights, perfect for adventures.', 92, 183, 25, 5, 14.0, '{"sunny": 0.7, "cloudy": 0.2, "rainy": 0.05, "stormy": 0.05, "windy": 0.05}', 'summer.png'),
('autumn', 'Autumn', 'A season of harvest and changing colors as the land prepares for winter.', 184, 274, 15, 7, 11.0, '{"sunny": 0.3, "cloudy": 0.4, "rainy": 0.2, "foggy": 0.1, "windy": 0.2}', 'autumn.png'),
('winter', 'Winter', 'The coldest season with short days and long nights, presenting unique challenges.', 275, 365, 0, 5, 9.0, '{"sunny": 0.2, "cloudy": 0.3, "snowy": 0.4, "foggy": 0.1, "windy": 0.1}', 'winter.png');

-- Initialize game time
INSERT INTO game_time (current_date, current_time, day_of_year, current_season, sunrise_time, sunset_time, is_day_time) VALUES
(CURDATE(), '12:00:00', DAYOFYEAR(CURDATE()), 'spring', '06:00:00', '18:00:00', 1);

-- Insert sample districts for Eldoria (capital city, location_id 1)
INSERT INTO districts (location_id, name, description, type, safety_level, wealth_level, population, image) VALUES
(1, 'Royal District', 'The heart of Eldoria where the ruling monarch resides, surrounded by government buildings and noble estates.', 'government', 95, 90, 2000, 'royal_district.png'),
(1, 'Noble Quarter', 'An exclusive district with elegant mansions and high-end establishments catering to the city\'s aristocracy.', 'noble', 85, 95, 5000, 'noble_quarter.png'),
(1, 'Merchant District', 'A bustling commercial zone filled with shops, trading companies, and markets of all kinds.', 'commercial', 75, 70, 15000, 'merchant_district.png'),
(1, 'Plaza of Magic', 'A district centered around the Arcane Academy, with shops selling magical items and scholars discussing the arcane arts.', 'magical', 80, 75, 3000, 'plaza_of_magic.png'),
(1, 'Artisan Quarter', 'Home to skilled craftspeople, with workshops and stores selling high-quality wares.', 'commercial', 70, 65, 7000, 'artisan_quarter.png'),
(1, 'Residential Heights', 'A peaceful neighborhood with well-built homes for the city\'s middle class.', 'residential', 80, 60, 20000, 'residential_heights.png'),
(1, 'Harbor District', 'A lively waterfront area with docks, warehouses, and establishments catering to sailors and traders.', 'commercial', 60, 50, 12000, 'harbor_district.png'),
(1, 'Military Ward', 'Home to the city guard barracks, training grounds, and armories, with a strong militaristic atmosphere.', 'military', 90, 55, 5000, 'military_ward.png'),
(1, 'Lowtown', 'A poorer district with affordable housing and humble establishments for the working class.', 'residential', 50, 30, 25000, 'lowtown.png'),
(1, 'The Gutters', 'The poorest section of the city, with ramshackle buildings and a reputation for crime.', 'slums', 20, 10, 10000, 'the_gutters.png');

-- Insert points of interest for Merchant District (district_id 3)
INSERT INTO points_of_interest (district_id, name, description, type, subtype, quality, popularity, open_hour, close_hour, image) VALUES
(3, 'Grand Bazaar', 'An enormous covered market with hundreds of stalls selling everything imaginable.', 'marketplace', 'general', 75, 90, 6, 22, 'grand_bazaar.png'),
(3, 'The Golden Coin', 'A prestigious trading company dealing in goods from across the realm.', 'shop', 'trading', 80, 70, 8, 18, 'golden_coin.png'),
(3, 'Silvermane Jewelers', 'A high-end jewelry shop known for exquisite craftsmanship.', 'shop', 'jewelry', 85, 60, 9, 19, 'silvermane_jewelers.png'),
(3, 'The Hungry Griffin', 'A popular mid-range restaurant serving hearty meals to shoppers and merchants.', 'restaurant', 'general', 65, 80, 7, 23, 'hungry_griffin.png'),
(3, 'Merchant\'s Rest', 'A tavern frequented by traders looking to make deals over a drink.', 'tavern', 'business', 70, 75, 10, 2, 'merchants_rest.png'),
(3, 'Greenleaf Park', 'A small landscaped garden offering respite from the busy market streets.', 'park', 'garden', 75, 65, 0, 24, 'greenleaf_park.png');

-- Insert points of interest for Plaza of Magic (district_id 4)
INSERT INTO points_of_interest (district_id, name, description, type, subtype, quality, popularity, open_hour, close_hour, image) VALUES
(4, 'Arcane Academy', 'A prestigious institution for the study of magic.', 'guild', 'magic', 90, 75, 8, 20, 'arcane_academy.png'),
(4, 'Scrolls & Tomes', 'A well-stocked bookstore specializing in magical texts and scrolls.', 'shop', 'books', 80, 70, 9, 19, 'scrolls_and_tomes.png'),
(4, 'Enchanted Emporium', 'A shop selling various magical items, potions, and components.', 'shop', 'magical_items', 85, 80, 8, 18, 'enchanted_emporium.png'),
(4, 'The Conjured Feast', 'A unique restaurant where food is prepared with culinary magic.', 'restaurant', 'magical', 80, 85, 11, 23, 'conjured_feast.png'),
(4, 'Starlight Garden', 'A beautiful park with plants that glow and change colors throughout the day.', 'park', 'magical', 90, 75, 0, 24, 'starlight_garden.png');

-- Insert menu items for The Hungry Griffin restaurant (poi_id 4)
INSERT INTO menu_items (poi_id, name, description, type, price, quality, health_restore, happiness_bonus, image) VALUES
(4, 'Merchant\'s Plate', 'A filling plate of roasted meat, potatoes, and vegetables.', 'food', 15, 65, 30, 15, 'merchants_plate.png'),
(4, 'Traveler\'s Stew', 'A hearty stew with meat, vegetables, and barley.', 'food', 10, 60, 25, 12, 'travelers_stew.png'),
(4, 'Fresh Bread Basket', 'Warm, freshly baked bread with butter.', 'food', 5, 70, 15, 10, 'bread_basket.png'),
(4, 'Griffin\'s Roast', 'The house specialty: a large platter of roasted meats.', 'food', 25, 75, 40, 20, 'griffins_roast.png'),
(4, 'Apple Pie', 'Sweet apple pie with cinnamon.', 'food', 8, 70, 15, 20, 'apple_pie.png'),
(4, 'Ale', 'Local brew with a rich flavor.', 'drink', 4, 60, 5, 8, 'ale.png'),
(4, 'Wine', 'Red wine from nearby vineyards.', 'drink', 7, 65, 5, 10, 'wine.png'),
(4, 'Fruit Juice', 'Fresh juice made from seasonal fruits.', 'drink', 3, 75, 10, 7, 'fruit_juice.png');

-- Insert menu items for The Conjured Feast restaurant (poi_id 9)
INSERT INTO menu_items (poi_id, name, description, type, price, quality, health_restore, happiness_bonus, effects, image) VALUES
(9, 'Levitating Soup', 'A soup that floats above the bowl and never spills.', 'food', 20, 80, 20, 15, 'Minor Levitation (1 hour)', 'levitating_soup.png'),
(9, 'Color-Changing Salad', 'A salad that changes colors based on the eater\'s mood.', 'food', 15, 75, 15, 18, 'Mood Reveal (30 minutes)', 'color_changing_salad.png'),
(9, 'Illusionary Steak', 'Appears as whatever meat the diner most desires.', 'food', 35, 85, 35, 25, 'Satisfied (2 hours)', 'illusionary_steak.png'),
(9, 'Flaming Dessert', 'A dessert perpetually on fire that never burns out or gets cold.', 'food', 25, 90, 20, 30, 'Fire Resistance (1 hour)', 'flaming_dessert.png'),
(9, 'Mana Potion Cocktail', 'A magical blue drink that sparkles and refreshes magical energy.', 'drink', 30, 85, 10, 15, 'Mana Regeneration +10% (1 hour)', 'mana_cocktail.png'),
(9, 'Shifting Spirits', 'A drink that changes flavor with every sip.', 'drink', 25, 80, 5, 25, 'Surprise (random minor buff)', 'shifting_spirits.png');

-- Insert park activities for Greenleaf Park (poi_id 6)
INSERT INTO park_activities (poi_id, name, description, happiness_gain, energy_cost, duration_minutes, required_weather, required_time) VALUES
(6, 'Relaxed Stroll', 'Take a peaceful walk through the garden paths.', 10, 5, 30, 'any', 'any'),
(6, 'Picnic', 'Enjoy a meal in the pleasant surroundings.', 15, 10, 60, 'sunny', 'day'),
(6, 'Reading', 'Find a quiet spot to read a book.', 12, 3, 45, 'any', 'day'),
(6, 'Meditation', 'Practice mindfulness in the tranquil environment.', 20, 8, 30, 'any', 'any'),
(6, 'Socializing', 'Meet other visitors and engage in conversation.', 15, 12, 45, 'any', 'any');

-- Insert park activities for Starlight Garden (poi_id 10)
INSERT INTO park_activities (poi_id, name, description, happiness_gain, energy_cost, duration_minutes, required_weather, required_time) VALUES
(10, 'Magical Flora Tour', 'Learn about the exotic magical plants in the garden.', 18, 10, 45, 'any', 'any'),
(10, 'Stargazing', 'Observe the stars through the enchanted canopy.', 25, 7, 60, 'any', 'night'),
(10, 'Mana Meditation', 'Meditate to enhance your magical abilities.', 20, 15, 30, 'any', 'any'),
(10, 'Light Show', 'Watch the plants create dazzling patterns of light.', 30, 5, 60, 'any', 'night'),
(10, 'Botanical Sketching', 'Draw the unique plants in the garden.', 15, 8, 45, 'any', 'day');

-- Initialize weather for main locations
INSERT INTO weather_system (location_id, current_weather, current_temperature, weather_intensity, next_change) VALUES
(1, 'sunny', 18, 50, DATE_ADD(NOW(), INTERVAL 8 HOUR)), -- Eldoria
(2, 'cloudy', 16, 60, DATE_ADD(NOW(), INTERVAL 6 HOUR)), -- Silvermist Forest
(3, 'foggy', 12, 70, DATE_ADD(NOW(), INTERVAL 10 HOUR)), -- Dwarven Mines
(4, 'sunny', 20, 40, DATE_ADD(NOW(), INTERVAL 12 HOUR)), -- Coastal Haven
(5, 'windy', 10, 80, DATE_ADD(NOW(), INTERVAL 4 HOUR)); -- Dragonfang Mountains 