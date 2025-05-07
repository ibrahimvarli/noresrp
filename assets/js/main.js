/**
 * NoRestRP - Fantasy Role-Playing
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Toggle burger menu animation
            const bars = mobileMenuToggle.querySelectorAll('.bar');
            if (mobileMenuToggle.classList.contains('active')) {
                bars[0].style.transform = 'rotate(-45deg) translate(-5px, 6px)';
                bars[1].style.opacity = '0';
                bars[2].style.transform = 'rotate(45deg) translate(-5px, -6px)';
            } else {
                bars[0].style.transform = 'none';
                bars[1].style.opacity = '1';
                bars[2].style.transform = 'none';
            }
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (navMenu.classList.contains('active') && !event.target.closest('.main-nav') && !event.target.closest('.mobile-menu-toggle')) {
            navMenu.classList.remove('active');
            if (mobileMenuToggle) {
                mobileMenuToggle.classList.remove('active');
                const bars = mobileMenuToggle.querySelectorAll('.bar');
                bars[0].style.transform = 'none';
                bars[1].style.opacity = '1';
                bars[2].style.transform = 'none';
            }
        }
    });
    
    // Theme toggle
    const themeToggle = document.querySelector('.theme-toggle');
    const body = document.body;
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            if (body.classList.contains('theme-dark')) {
                body.classList.remove('theme-dark');
                body.classList.add('theme-light');
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.remove('theme-light');
                body.classList.add('theme-dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    }
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        body.className = '';
        body.classList.add('theme-' + savedTheme);
    }
    
    // Flash message close button
    const flashMessage = document.querySelector('.flash-message');
    const closeButton = document.querySelector('.flash-message .close-button');
    
    if (flashMessage && closeButton) {
        closeButton.addEventListener('click', function() {
            flashMessage.style.opacity = '0';
            setTimeout(function() {
                flashMessage.style.display = 'none';
            }, 300);
        });
        
        // Auto close after 5 seconds
        setTimeout(function() {
            flashMessage.style.opacity = '0';
            setTimeout(function() {
                flashMessage.style.display = 'none';
            }, 300);
        }, 5000);
    }
    
    // Character creation form
    const characterForm = document.getElementById('character-form');
    if (characterForm) {
        const raceSelect = document.getElementById('race');
        const classSelect = document.getElementById('class');
        const statsContainer = document.getElementById('character-stats');
        
        // Update stats preview when race or class changes
        function updateStatsPreview() {
            if (!raceSelect || !classSelect || !statsContainer) return;
            
            const race = raceSelect.value;
            const characterClass = classSelect.value;
            
            // Base stats
            let stats = {
                health: 100,
                mana: 50,
                strength: 10,
                dexterity: 10,
                intelligence: 10,
                wisdom: 10,
                charisma: 10
            };
            
            // Apply race bonuses
            switch (race) {
                case 'human':
                    stats.strength += 2;
                    stats.charisma += 2;
                    break;
                case 'elf':
                    stats.dexterity += 3;
                    stats.intelligence += 1;
                    break;
                case 'dwarf':
                    stats.strength += 3;
                    stats.health += 20;
                    break;
                case 'orc':
                    stats.strength += 4;
                    stats.intelligence -= 1;
                    break;
            }
            
            // Apply class bonuses
            switch (characterClass) {
                case 'warrior':
                    stats.health += 30;
                    stats.strength += 3;
                    break;
                case 'mage':
                    stats.mana += 30;
                    stats.intelligence += 3;
                    break;
                case 'rogue':
                    stats.dexterity += 3;
                    stats.charisma += 1;
                    break;
                case 'cleric':
                    stats.wisdom += 3;
                    stats.mana += 15;
                    stats.health += 10;
                    break;
            }
            
            // Update stats display
            statsContainer.innerHTML = `
                <div class="stat-row">
                    <div class="stat">
                        <div class="stat-name">Health</div>
                        <div class="stat-value health">${stats.health}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-name">Mana</div>
                        <div class="stat-value mana">${stats.mana}</div>
                    </div>
                </div>
                <div class="stat-row">
                    <div class="stat">
                        <div class="stat-name">Strength</div>
                        <div class="stat-value">${stats.strength}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-name">Dexterity</div>
                        <div class="stat-value">${stats.dexterity}</div>
                    </div>
                </div>
                <div class="stat-row">
                    <div class="stat">
                        <div class="stat-name">Intelligence</div>
                        <div class="stat-value">${stats.intelligence}</div>
                    </div>
                    <div class="stat">
                        <div class="stat-name">Wisdom</div>
                        <div class="stat-value">${stats.wisdom}</div>
                    </div>
                </div>
                <div class="stat-row">
                    <div class="stat">
                        <div class="stat-name">Charisma</div>
                        <div class="stat-value">${stats.charisma}</div>
                    </div>
                </div>
            `;
        }
        
        if (raceSelect && classSelect) {
            raceSelect.addEventListener('change', updateStatsPreview);
            classSelect.addEventListener('change', updateStatsPreview);
            
            // Initialize stats preview
            updateStatsPreview();
        }
    }
    
    // Character avatar preview
    const avatarUpload = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatar-preview');
    
    if (avatarUpload && avatarPreview) {
        avatarUpload.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Inventory item tooltips
    const inventoryItems = document.querySelectorAll('.inventory-item');
    
    inventoryItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            const tooltip = this.querySelector('.item-tooltip');
            if (tooltip) {
                tooltip.style.display = 'block';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.item-tooltip');
            if (tooltip) {
                tooltip.style.display = 'none';
            }
        });
    });
    
    // Battle animations
    const battleButtons = document.querySelectorAll('.battle-action-btn');
    const playerCharacter = document.querySelector('.player-character');
    const enemyCharacter = document.querySelector('.enemy-character');
    
    if (battleButtons.length && playerCharacter && enemyCharacter) {
        battleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const actionType = this.dataset.action;
                
                if (actionType === 'attack') {
                    playerCharacter.classList.add('attack-animation');
                    setTimeout(() => {
                        enemyCharacter.classList.add('damage-animation');
                        updateHealthBar(enemyCharacter.querySelector('.health-bar'), -10);
                    }, 300);
                    
                    setTimeout(() => {
                        playerCharacter.classList.remove('attack-animation');
                        enemyCharacter.classList.remove('damage-animation');
                        
                        // Enemy counter-attack
                        enemyCharacter.classList.add('attack-animation');
                        setTimeout(() => {
                            playerCharacter.classList.add('damage-animation');
                            updateHealthBar(playerCharacter.querySelector('.health-bar'), -5);
                        }, 300);
                        
                        setTimeout(() => {
                            enemyCharacter.classList.remove('attack-animation');
                            playerCharacter.classList.remove('damage-animation');
                        }, 600);
                        
                    }, 800);
                }
                else if (actionType === 'spell') {
                    playerCharacter.classList.add('cast-animation');
                    
                    setTimeout(() => {
                        const spellEffect = document.createElement('div');
                        spellEffect.className = 'spell-effect';
                        enemyCharacter.appendChild(spellEffect);
                        
                        setTimeout(() => {
                            enemyCharacter.classList.add('damage-animation');
                            updateHealthBar(enemyCharacter.querySelector('.health-bar'), -15);
                            updateManaBar(playerCharacter.querySelector('.mana-bar'), -10);
                        }, 200);
                        
                        setTimeout(() => {
                            enemyCharacter.removeChild(spellEffect);
                        }, 500);
                    }, 400);
                    
                    setTimeout(() => {
                        playerCharacter.classList.remove('cast-animation');
                        enemyCharacter.classList.remove('damage-animation');
                    }, 1000);
                }
            });
        });
        
        function updateHealthBar(healthBar, change) {
            if (!healthBar) return;
            
            const currentWidth = parseInt(healthBar.style.width) || 100;
            const newWidth = Math.max(0, Math.min(100, currentWidth + change));
            healthBar.style.width = newWidth + '%';
            
            // Update color based on health level
            if (newWidth < 25) {
                healthBar.style.backgroundColor = '#e74c3c';
            } else if (newWidth < 50) {
                healthBar.style.backgroundColor = '#f39c12';
            } else {
                healthBar.style.backgroundColor = '#2ecc71';
            }
        }
        
        function updateManaBar(manaBar, change) {
            if (!manaBar) return;
            
            const currentWidth = parseInt(manaBar.style.width) || 100;
            const newWidth = Math.max(0, Math.min(100, currentWidth + change));
            manaBar.style.width = newWidth + '%';
        }
    }
    
    // World map interactive tooltips
    const mapLocations = document.querySelectorAll('.map-location');
    const locationInfo = document.querySelector('.location-info');
    
    if (mapLocations.length && locationInfo) {
        mapLocations.forEach(location => {
            location.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all locations
                mapLocations.forEach(loc => loc.classList.remove('active'));
                
                // Add active class to clicked location
                this.classList.add('active');
                
                // Update location info
                const locationId = this.dataset.location;
                const locationName = this.dataset.name;
                const locationDesc = this.dataset.description;
                const locationImage = this.dataset.image;
                
                locationInfo.innerHTML = `
                    <h3>${locationName}</h3>
                    <div class="location-image">
                        <img src="${locationImage}" alt="${locationName}">
                    </div>
                    <p>${locationDesc}</p>
                    <a href="?page=location&id=${locationId}" class="btn btn-primary">Visit Location</a>
                `;
                
                locationInfo.style.display = 'block';
            });
        });
    }
}); 