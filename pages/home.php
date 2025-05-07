<div class="home-container">
    <div class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Welcome to NoRestRP</h1>
            <p class="hero-subtitle">Embark on an Epic Fantasy Journey</p>
            <?php if (!isLoggedIn()): ?>
                <div class="hero-buttons">
                    <a href="?page=register" class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> Create Account</a>
                    <a href="?page=login" class="btn btn-secondary btn-lg"><i class="fas fa-sign-in-alt"></i> Login</a>
                </div>
            <?php else: ?>
                <div class="hero-buttons">
                    <a href="?page=characters" class="btn btn-primary btn-lg"><i class="fas fa-user-shield"></i> My Characters</a>
                    <a href="?page=world" class="btn btn-secondary btn-lg"><i class="fas fa-globe"></i> Explore World</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="features-section">
        <div class="section-header">
            <h2>Begin Your Adventure</h2>
            <p>Discover the mystical world of Eldoria</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-hat-wizard"></i>
                </div>
                <h3>Create Unique Characters</h3>
                <p>Choose from various races and classes to create a character that fits your playstyle. Level up, gain new abilities, and customize your appearance.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-dragon"></i>
                </div>
                <h3>Epic Battles</h3>
                <p>Face fearsome beasts, crafty goblins, and legendary dragons in tactical turn-based combat. Use your skills wisely to emerge victorious.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-scroll"></i>
                </div>
                <h3>Compelling Quests</h3>
                <p>Embark on hundreds of quests ranging from simple tasks to epic adventures that will shape the fate of the realm.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Vibrant Community</h3>
                <p>Join thousands of players in a vibrant role-playing community. Make friends, form alliances, or engage in friendly rivalry.</p>
            </div>
        </div>
    </div>
    
    <div class="world-section">
        <div class="section-header">
            <h2>Explore a Rich Fantasy World</h2>
            <p>Venture through diverse lands filled with mystery and adventure</p>
        </div>
        
        <div class="world-preview">
            <div class="world-card">
                <img src="assets/images/locations/eldoria.jpg" alt="Eldoria City">
                <div class="world-card-content">
                    <h3>Eldoria City</h3>
                    <p>The majestic capital of the realm where adventurers gather to trade, share stories, and prepare for quests.</p>
                </div>
            </div>
            
            <div class="world-card">
                <img src="assets/images/locations/silvermist.jpg" alt="Silvermist Forest">
                <div class="world-card-content">
                    <h3>Silvermist Forest</h3>
                    <p>An ancient forest filled with magical creatures, hidden treasures, and mysteries waiting to be uncovered.</p>
                </div>
            </div>
            
            <div class="world-card">
                <img src="assets/images/locations/dragonpeaks.jpg" alt="Dragon Peaks">
                <div class="world-card-content">
                    <h3>Dragonfang Mountains</h3>
                    <p>Treacherous peaks home to dragons and fearsome beasts, but also holding invaluable treasures.</p>
                </div>
            </div>
        </div>
        
        <div class="cta-button">
            <a href="?page=world" class="btn btn-primary"><i class="fas fa-map-marked-alt"></i> Explore the World Map</a>
        </div>
    </div>
    
    <div class="races-section">
        <div class="section-header">
            <h2>Choose Your Race</h2>
            <p>Each race has unique abilities and cultural backgrounds</p>
        </div>
        
        <div class="races-carousel">
            <div class="race-card">
                <div class="race-image">
                    <img src="assets/images/races/human.jpg" alt="Human">
                </div>
                <div class="race-info">
                    <h3>Human</h3>
                    <p>Versatile and ambitious, humans can excel in any role. Their adaptability makes them natural leaders.</p>
                    <ul class="race-stats">
                        <li><span>Strength</span> +2</li>
                        <li><span>Charisma</span> +2</li>
                        <li><span>Special</span> Quick Learner</li>
                    </ul>
                </div>
            </div>
            
            <div class="race-card">
                <div class="race-image">
                    <img src="assets/images/races/elf.jpg" alt="Elf">
                </div>
                <div class="race-info">
                    <h3>Elf</h3>
                    <p>Ancient and graceful, elves excel in magic and precision combat. Their long lifespans give them unique perspective.</p>
                    <ul class="race-stats">
                        <li><span>Dexterity</span> +3</li>
                        <li><span>Intelligence</span> +1</li>
                        <li><span>Special</span> Night Vision</li>
                    </ul>
                </div>
            </div>
            
            <div class="race-card">
                <div class="race-image">
                    <img src="assets/images/races/dwarf.jpg" alt="Dwarf">
                </div>
                <div class="race-info">
                    <h3>Dwarf</h3>
                    <p>Stout and resilient, dwarves are master craftsmen and formidable fighters with legendary endurance.</p>
                    <ul class="race-stats">
                        <li><span>Strength</span> +3</li>
                        <li><span>Health</span> +20</li>
                        <li><span>Special</span> Stonecunning</li>
                    </ul>
                </div>
            </div>
            
            <div class="race-card">
                <div class="race-image">
                    <img src="assets/images/races/orc.jpg" alt="Orc">
                </div>
                <div class="race-info">
                    <h3>Orc</h3>
                    <p>Powerful and fearsome, orcs are born warriors with unmatched physical prowess and battle fury.</p>
                    <ul class="race-stats">
                        <li><span>Strength</span> +4</li>
                        <li><span>Intelligence</span> -1</li>
                        <li><span>Special</span> Battle Rage</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="classes-section">
        <div class="section-header">
            <h2>Master Your Class</h2>
            <p>Hone your skills and abilities in your chosen profession</p>
        </div>
        
        <div class="classes-grid">
            <div class="class-card warrior">
                <div class="class-icon">
                    <i class="fas fa-sword"></i>
                </div>
                <h3>Warrior</h3>
                <p>Masters of combat who rely on physical strength and weapon skills to overcome their enemies.</p>
                <ul class="class-skills">
                    <li>Heroic Strike</li>
                    <li>Shield Bash</li>
                    <li>Battle Shout</li>
                </ul>
            </div>
            
            <div class="class-card mage">
                <div class="class-icon">
                    <i class="fas fa-hat-wizard"></i>
                </div>
                <h3>Mage</h3>
                <p>Wielders of arcane magic who can summon devastating spells to control the battlefield.</p>
                <ul class="class-skills">
                    <li>Fireball</li>
                    <li>Ice Nova</li>
                    <li>Arcane Missiles</li>
                </ul>
            </div>
            
            <div class="class-card rogue">
                <div class="class-icon">
                    <i class="fas fa-mask"></i>
                </div>
                <h3>Rogue</h3>
                <p>Stealthy operatives who excel at mobility, precision strikes, and avoiding detection.</p>
                <ul class="class-skills">
                    <li>Backstab</li>
                    <li>Poison Blade</li>
                    <li>Shadow Step</li>
                </ul>
            </div>
            
            <div class="class-card cleric">
                <div class="class-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <h3>Cleric</h3>
                <p>Divine spellcasters who can heal allies, banish undead, and call upon their deity's power.</p>
                <ul class="class-skills">
                    <li>Healing Light</li>
                    <li>Holy Shield</li>
                    <li>Divine Smite</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="join-section">
        <div class="join-overlay"></div>
        <div class="join-content">
            <h2>Your Legend Awaits</h2>
            <p>Join thousands of adventurers already exploring the realm of Eldoria</p>
            <?php if (!isLoggedIn()): ?>
                <a href="?page=register" class="btn btn-glow btn-lg">Begin Your Adventure</a>
            <?php else: ?>
                <a href="?page=characters" class="btn btn-glow btn-lg">Continue Your Journey</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!isLoggedIn()): ?>
    <div class="testimonials-section">
        <div class="section-header">
            <h2>What Players Are Saying</h2>
            <p>Join our thriving community of adventurers</p>
        </div>
        
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"The most immersive fantasy role-playing experience I've found online. The community is amazing and the world feels alive!"</p>
                </div>
                <div class="testimonial-author">
                    <img src="assets/images/avatars/player1.jpg" alt="Player Avatar">
                    <div class="author-info">
                        <h4>DragonSlayer92</h4>
                        <p>Playing since 2021</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"I've never found a role-playing game with such a rich lore and responsive development team. They're always adding new content!"</p>
                </div>
                <div class="testimonial-author">
                    <img src="assets/images/avatars/player2.jpg" alt="Player Avatar">
                    <div class="author-info">
                        <h4>ElvenArcher</h4>
                        <p>Playing since 2020</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"The quests are so well-written and engaging. I've made real friends here and we adventure together weekly!"</p>
                </div>
                <div class="testimonial-author">
                    <img src="assets/images/avatars/player3.jpg" alt="Player Avatar">
                    <div class="author-info">
                        <h4>MysticHealer</h4>
                        <p>Playing since 2021</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add home page specific CSS -->
<style>
/* Hero Section */
.hero-section {
    position: relative;
    height: 600px;
    background-image: url('assets/images/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    margin-top: -4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: white;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(0,0,0,0.7));
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    padding: 0 2rem;
}

.hero-title {
    font-size: 5rem;
    margin-bottom: 1rem;
    text-shadow: 0 2px 10px rgba(0,0,0,0.8);
}

.hero-subtitle {
    font-size: 2.2rem;
    margin-bottom: 3rem;
    text-shadow: 0 2px 8px rgba(0,0,0,0.8);
}

.hero-buttons {
    display: flex;
    gap: 2rem;
    justify-content: center;
}

.btn-lg {
    padding: 1.2rem 2.5rem;
    font-size: 1.8rem;
}

/* Features Section */
.features-section {
    padding: 8rem 2rem;
    background-color: var(--bg-primary);
}

.section-header {
    text-align: center;
    margin-bottom: 6rem;
}

.section-header h2 {
    font-size: 3.6rem;
    margin-bottom: 1rem;
}

.section-header p {
    font-size: 1.8rem;
    color: var(--text-secondary);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 4rem;
    max-width: var(--container-width);
    margin: 0 auto;
}

.feature-card {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 3rem 2rem;
    text-align: center;
    box-shadow: 0 5px 15px var(--shadow);
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-10px);
}

.feature-icon {
    font-size: 4rem;
    color: var(--accent-primary);
    margin-bottom: 2rem;
}

.feature-card h3 {
    font-size: 2.2rem;
    margin-bottom: 1.5rem;
}

.feature-card p {
    color: var(--text-secondary);
}

/* World Section */
.world-section {
    padding: 8rem 2rem;
    background-color: var(--bg-tertiary);
}

.world-preview {
    display: flex;
    gap: 3rem;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 5rem;
}

.world-card {
    flex: 1 1 300px;
    max-width: 350px;
    border-radius: var(--border-radius);
    overflow: hidden;
    background-color: var(--bg-secondary);
    box-shadow: 0 5px 15px var(--shadow);
    transition: transform 0.3s ease;
}

.world-card:hover {
    transform: translateY(-10px);
}

.world-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.world-card-content {
    padding: 2rem;
}

.world-card h3 {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.world-card p {
    color: var(--text-secondary);
}

.cta-button {
    text-align: center;
}

.btn-primary {
    background-color: var(--accent-primary);
    color: white;
    padding: 1.2rem 2.4rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.btn-primary:hover {
    background-color: var(--accent-secondary);
}

.btn-secondary {
    background-color: transparent;
    color: white;
    padding: 1.2rem 2.4rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    border: 2px solid white;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

/* Races Section */
.races-section {
    padding: 8rem 2rem;
    background-color: var(--bg-primary);
}

.races-carousel {
    display: flex;
    gap: 3rem;
    flex-wrap: wrap;
    justify-content: center;
}

.race-card {
    flex: 1 1 280px;
    max-width: 500px;
    display: flex;
    flex-direction: column;
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 5px 15px var(--shadow);
    transition: transform 0.3s ease;
}

.race-card:hover {
    transform: translateY(-10px);
}

.race-image {
    height: 250px;
    overflow: hidden;
}

.race-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.race-card:hover .race-image img {
    transform: scale(1.05);
}

.race-info {
    padding: 2rem;
    flex: 1;
}

.race-info h3 {
    font-size: 2.2rem;
    margin-bottom: 1rem;
}

.race-info p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

.race-stats {
    list-style: none;
    margin: 0;
    padding: 0;
}

.race-stats li {
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 0;
    border-bottom: 1px solid var(--border);
}

.race-stats li:last-child {
    border-bottom: none;
}

.race-stats span {
    font-weight: 600;
    color: var(--accent-primary);
}

/* Classes Section */
.classes-section {
    padding: 8rem 2rem;
    background-color: var(--bg-tertiary);
}

.classes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 3rem;
    max-width: var(--container-width);
    margin: 0 auto;
}

.class-card {
    padding: 3rem 2rem;
    border-radius: var(--border-radius);
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 5px 15px var(--shadow);
    transition: transform 0.3s ease;
}

.class-card:hover {
    transform: translateY(-10px);
}

.class-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    opacity: 0.15;
    z-index: -1;
}

.class-card.warrior::before {
    background-image: url('assets/images/classes/warrior-bg.jpg');
}

.class-card.mage::before {
    background-image: url('assets/images/classes/mage-bg.jpg');
}

.class-card.rogue::before {
    background-image: url('assets/images/classes/rogue-bg.jpg');
}

.class-card.cleric::before {
    background-image: url('assets/images/classes/cleric-bg.jpg');
}

.class-card.warrior {
    background-color: rgba(231, 76, 60, 0.2);
    border: 2px solid var(--warrior-color);
}

.class-card.mage {
    background-color: rgba(52, 152, 219, 0.2);
    border: 2px solid var(--mage-color);
}

.class-card.rogue {
    background-color: rgba(241, 196, 15, 0.2);
    border: 2px solid var(--rogue-color);
}

.class-card.cleric {
    background-color: rgba(46, 204, 113, 0.2);
    border: 2px solid var(--cleric-color);
}

.class-icon {
    font-size: 4rem;
    margin-bottom: 2rem;
}

.warrior .class-icon {
    color: var(--warrior-color);
}

.mage .class-icon {
    color: var(--mage-color);
}

.rogue .class-icon {
    color: var(--rogue-color);
}

.cleric .class-icon {
    color: var(--cleric-color);
}

.class-card h3 {
    font-size: 2.2rem;
    margin-bottom: 1.5rem;
}

.class-card p {
    margin-bottom: 2rem;
}

.class-skills {
    list-style: none;
    margin: 0;
    padding: 0;
    text-align: left;
}

.class-skills li {
    padding: 0.8rem 1.2rem;
    margin-bottom: 0.8rem;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: var(--border-radius);
    font-weight: 600;
}

.warrior .class-skills li {
    color: var(--warrior-color);
}

.mage .class-skills li {
    color: var(--mage-color);
}

.rogue .class-skills li {
    color: var(--rogue-color);
}

.cleric .class-skills li {
    color: var(--cleric-color);
}

/* Join Section */
.join-section {
    position: relative;
    padding: 12rem 2rem;
    background-image: url('assets/images/join-bg.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: white;
    text-align: center;
}

.join-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, rgba(0,0,0,0.8), rgba(0,0,0,0.5), rgba(0,0,0,0.8));
}

.join-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
}

.join-content h2 {
    font-size: 4.2rem;
    margin-bottom: 1.5rem;
    text-shadow: 0 2px 10px rgba(0,0,0,0.8);
}

.join-content p {
    font-size: 2rem;
    margin-bottom: 3rem;
    text-shadow: 0 2px 8px rgba(0,0,0,0.8);
}

.btn-glow {
    background-color: var(--accent-primary);
    color: white;
    padding: 1.5rem 3rem;
    font-size: 2rem;
    border-radius: var(--border-radius);
    box-shadow: 0 0 20px var(--accent-primary);
    transition: all 0.3s ease;
}

.btn-glow:hover {
    background-color: var(--accent-secondary);
    box-shadow: 0 0 30px var(--accent-secondary);
    transform: scale(1.05);
}

/* Testimonials Section */
.testimonials-section {
    padding: 8rem 2rem;
    background-color: var(--bg-primary);
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 3rem;
    max-width: var(--container-width);
    margin: 0 auto;
}

.testimonial-card {
    background-color: var(--bg-secondary);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 5px 15px var(--shadow);
}

.testimonial-content {
    padding: 3rem;
    position: relative;
}

.testimonial-content::before {
    content: '"';
    position: absolute;
    top: -10px;
    left: 20px;
    font-size: 10rem;
    font-family: serif;
    color: var(--accent-primary);
    opacity: 0.2;
    line-height: 1;
}

.testimonial-content p {
    position: relative;
    z-index: 1;
    font-style: italic;
    font-size: 1.6rem;
}

.testimonial-author {
    display: flex;
    align-items: center;
    padding: 2rem 3rem;
    background-color: var(--bg-tertiary);
}

.testimonial-author img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 1.5rem;
    border: 2px solid var(--accent-primary);
}

.author-info h4 {
    font-size: 1.6rem;
    margin-bottom: 0.2rem;
}

.author-info p {
    font-size: 1.2rem;
    color: var(--text-secondary);
    margin: 0;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .hero-title {
        font-size: 3.6rem;
    }
    
    .hero-subtitle {
        font-size: 1.8rem;
    }
    
    .hero-buttons {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .join-content h2 {
        font-size: 3.2rem;
    }
    
    .join-content p {
        font-size: 1.6rem;
    }
}

@media (max-width: 480px) {
    .hero-section {
        height: 500px;
    }
    
    .section-header h2 {
        font-size: 2.8rem;
    }
    
    .section-header p {
        font-size: 1.6rem;
    }
}
</style> 