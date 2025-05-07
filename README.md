# NoRestRP - Fantasy Role-Playing Web Application

A modern, fantasy-themed role-playing website built with PHP, MySQL, JavaScript, and CSS.

## Features

- **User Authentication**: Register, login, and manage your account
- **Character Management**: Create and customize characters with different races and classes
- **Immersive World**: Explore various locations, battle monsters, and complete quests
- **Inventory System**: Collect, equip, and manage items
- **Skill System**: Learn and upgrade abilities based on your character's class
- **Forum System**: Interact with other players through in-character and out-of-character discussions

## Races

- **Human**: Versatile and adaptable, excelling in leadership roles
- **Elf**: Graceful and agile, with innate magical abilities
- **Dwarf**: Sturdy and resilient, with exceptional crafting skills
- **Orc**: Physically powerful, with unparalleled strength in battle

## Classes

- **Warrior**: Masters of combat who rely on strength and weapon skills
- **Mage**: Wielders of arcane magic who can cast powerful spells
- **Rogue**: Stealthy operatives with high agility and precision strikes
- **Cleric**: Divine spellcasters who can heal allies and banish undead

## Installation

1. Clone the repository to your web server
2. Import the `database.sql` file to create the necessary database and tables
3. Configure database connection in `includes/config.php`
4. Ensure the server has PHP 7.4+ and MySQL 5.7+ installed
5. Access the application through your web browser

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Directory Structure

```
├── assets/               # Static assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   ├── images/           # Images
│   │   ├── avatars/      # User avatars
│   │   ├── classes/      # Class-related images
│   │   ├── locations/    # Location images
│   │   ├── portraits/    # Character portraits
│   │   └── races/        # Race-related images
│   └── fonts/            # Font files
├── components/           # Reusable UI components
├── includes/             # Core PHP files
│   ├── config.php        # Configuration settings
│   ├── db.php            # Database connection
│   ├── functions.php     # Utility functions
│   ├── header.php        # Header template
│   └── footer.php        # Footer template
├── pages/                # Page templates
│   ├── home.php          # Homepage
│   ├── login.php         # Login page
│   ├── register.php      # Registration page
│   ├── characters.php    # Character management
│   └── ...               # Other pages
├── index.php             # Main entry point
├── database.sql          # Database structure and initial data
└── README.md             # This file
```

## Credits

Developed by NoRestRP Team

## License

This project is licensed under the MIT License. 
