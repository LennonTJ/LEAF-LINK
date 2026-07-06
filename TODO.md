# TODO - Uniform Agriculture Styling + Sidebar Dashboards

## Step 1: Inspect + plan

- [x] Identified styling entry point: `assets/css/style.css`
- [x] Found dashboard pages with duplicated/uneven sidebar markup: `admin/dashboard.php`, `grower/dashboard.php`, `contractor/dashboard.php`
- [x] Confirmed navigation should be role-specific (Admin vs Grower vs Contractor) but displayed using a consistent sidebar UI.

## Step 2: Implement global agriculture theme

- [x] Edit `assets/css/style.css`
  - [x] Removed hard override `body{ background:red !important; }`
  - [x] Standardized base agriculture colors/typography/spacing in `style.css`

## Step 3: Unify dashboard layout + sidebar links

- [x] Edit `admin/dashboard.php` (uniform `.layout/.sidebar/.content` + consistent sidebar top links)
- [x] Edit `grower/dashboard.php` (uniform `.layout/.sidebar/.content` + sidebar links)
- [x] Edit `contractor/dashboard.php` (uniform `.layout/.sidebar/.content` + sidebar links)

## Step 4: Validate

- [ ] Open each dashboard in browser and verify consistent theme + sidebar rendering (admin/grower/contractor)
