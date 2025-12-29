# Client List Shortcode

Displays a client list from a user-selected text file via shortcode, with optional 3-column tabbed navigation.

## Usage

1. Go to **Settings → Client List** and choose a plain text file from the Media Library.
2. Add the shortcode to any post or page:

### Attributes

- `show_tabs` (default: `true`)  
  - `true` – show A–Z tab navigation plus “All”
  - `false` – render a single 3-column list

- `letter` (default: empty)  
  - When `show_tabs="false"`, filter by initial letter, e.g. `[client_list show_tabs="false" letter="B"]`.  
  - `letter="A"` also includes numeric-starting names.

## Filters

- `cls_client_list_clients( array $clients, array $atts )`
- `cls_client_list_filtered_clients( array $clients, string $letter, array $atts )`

## Requirements

- WordPress 5.0+
