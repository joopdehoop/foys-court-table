# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "Foys Blokkenschema" that displays a responsive table showing squash court reservations for 5 courts based on the Foys JSON API.

## Files and functions

foys-court-table.php - contains the plugin, and the following functions:
- foys_render_baantabel_anonymous - renders the anonymous version of the court reservations
- foys_get_reservering_info($reserveringen, $tijdvak) - gets the information about a reservation
- foys_admin_menu - adds the settings menu in the backend
- foys_admin_init - initializes the admin menu
- foys_allowed_paths_field - manages the field for whitelisted paths for the non-anonymous table
- foys_settings_page - shows the settings page
assets/frontend.css - contains the design

## Architecture

- **Single file plugin**: `foys-court-table.php` contains the entire plugin
- **WordPress shortcode**: `[foys_baantabel]` renders the reservation table
- **API integration**: Fetches data from WordPress REST API endpoint `foys-json/v1/reservations`
- **Time slots**: Displays 30-minute intervals from 9:00 to 22:30
- **Court display**: Shows first 5 courts from the API response

## Key Functions

- `foys_render_baantabel()`: Main shortcode handler that fetches data and renders the table
- `foys_is_bezet()`: Checks if a court is occupied during a specific time slot

## Data Structure

The plugin expects JSON data with this structure:
```json
{
  "inventoryItems": [
    {
      "name": "Court Name",
      "reservations": [
        {
          "startDateTime": "2024-01-01T10:00:00",
          "endDateTime": "2024-01-01T11:00:00"
        }
      ]
    }
  ]
}
```

## Development Notes

- No build process required - direct PHP development
- Plugin follows WordPress security practices (ABSPATH check, esc_html, wp_remote_get)
- Uses WordPress built-in functions for HTTP requests and output buffering
- CSS is embedded inline within the plugin for simplicity
- Time zone handling uses PHP's strtotime() with current date
