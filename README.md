# Foys Court Table

A WordPress plugin that displays a responsive table showing court reservations for 5 courts based on the Foys JSON API.

## 🏟️ Features

- **Real-time Court Display**: Shows availability for 5 squash courts with 30-minute time slots from 9:00 to 22:30
- **Dual Display Modes**: 
  - Anonymous table showing only occupied/free status
  - Named table displaying player names (with path-based access control)
- **Responsive Design**: Optimized for desktop, tablet, and mobile devices
- **Smart Name Processing**: Handles Dutch name prefixes (van, van der, de, etc.) correctly
- **Path-based Access Control**: Whitelist specific URLs for displaying player names
- **WordPress Integration**: Simple shortcode implementation with admin settings panel

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Access to Foys reservation system API

## 🚀 Installation

1. Download the plugin files
2. Upload the `foys-court-table` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings under Settings > Foys Blokkenschema

## ⚙️ Configuration

### Admin Settings

Navigate to **Settings > Foys Blokkenschema** in your WordPress admin:

- **API Key**: Enter your Foys API key for accessing reservation data
- **Whitelist Paths**: Specify paths where the non-anonymous table can be displayed (one path per line)

### Shortcodes

Use these shortcodes in your pages or posts:


[foys_baantabel]           // Displays full table with player names (path-restricted)
[foys_baantabel_anonymous] // Displays anonymous table (occupied/free only)


## 📊 Usage Examples

### Basic Implementation

// In a WordPress page or post
[foys_baantabel_anonymous]


### Path-restricted Implementation

// Configure whitelist paths in admin:
/squash/
/reservations/
/court-schedule/

// Then use the named version:
[foys_baantabel]


## 🏗️ Tech Stack

- **Backend**: PHP 7.4+, WordPress API
- **Frontend**: HTML5, CSS3 with responsive design
- **Data Source**: WordPress REST API integration
- **Security**: WordPress security practices (ABSPATH checks, input sanitization)

## 📁 Project Structure


foys-court-table/
├── foys-court-table.php    # Main plugin file with all functionality
└── assets/
    └── frontend.css        # Responsive styling for the court table



## 🎨 Responsive Design

The plugin features three breakpoints:

- **Desktop** (768px+): Full table with complete player names
- **Tablet** (480-768px): Condensed view with last names only
- **Mobile** (<480px): Compact layout optimized for small screens

## 🔧 API Integration

The plugin integrates with the Foys reservation system via WordPress REST API:

**Endpoint**: `wp-json/foys-json/v1/reservations`

**Expected JSON Structure**:

{
  "inventoryItems": [
    {
      "name": "Baan 1",
      "reservations": [
        {
          "startDateTime": "2025-08-05T18:30:00",
          "endDateTime": "2025-08-05T19:30:00",
          "players": [
            {
              "person": {
                "fullName": "John Doe"
              }
            }
          ]
        }
      ]
    }
  ]
}


## 🔒 Security Features

- WordPress security best practices implementation
- Input sanitization and output escaping
- Path-based access control for sensitive data
- ABSPATH security checks

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow WordPress coding standards
4. Test your changes thoroughly
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Development Guidelines

- Follow WordPress coding standards
- Maintain responsive design principles
- Test across different screen sizes
- Ensure security best practices
- Update documentation for new features

## 📝 License

This project is developed for internal use with the Foys reservation system.

## 👨‍💻 Author

**Elmer Smaling**

---

*This plugin is specifically designed for integration with the Foys squash court reservation system and provides a clean, responsive interface for displaying court availability.*
