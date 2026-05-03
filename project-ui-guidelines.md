# Dream Blanks POS System - UI/UX Guidelines & Component Library

## 1. Design Philosophy

### 1.1 Core Principles
- **Minimalist**: Clean interface with only essential elements
- **Professional**: Modern, polished look suitable for business environments
- **Intuitive**: Users should understand functionality without extensive training
- **Responsive**: Adapt seamlessly to different screen sizes
- **Accessible**: WCAG compliance for users with disabilities
- **Consistent**: Uniform design language throughout the application

### 1.2 Visual Identity
- **Primary Color Scheme**: Gray and White
- **Accent Colors**: Professional blue for actions, red for critical actions
- **Typography**: Modern sans-serif fonts (Arial, Segoe UI, or Roboto)
- **Spacing**: Consistent padding and margins (8px, 16px, 24px, 32px grid)
- **Elevation**: Subtle shadows for depth and layering

---

## 2. Color Palette

### 2.1 Primary Colors
```css
/* Neutrals */
--color-white: #FFFFFF;
--color-light-gray: #F5F5F5;
--color-gray-100: #E8E8E8;
--color-gray-300: #D0D0D0;
--color-gray-500: #808080;
--color-gray-700: #505050;
--color-dark-gray: #2D2D2D;
--color-black: #000000;

/* Accent Colors */
--color-primary: #0056B3;      /* Professional Blue */
--color-primary-light: #E6F0FF;
--color-success: #28A745;      /* Green */
--color-warning: #FFC107;      /* Amber */
--color-danger: #DC3545;       /* Red */
--color-info: #17A2B8;         /* Cyan */

/* Backgrounds */
--bg-primary: #FFFFFF;
--bg-secondary: #F5F5F5;
--bg-tertiary: #E8E8E8;
```

### 2.2 Usage Guidelines
- **White backgrounds** for main content areas
- **Light gray** for secondary sections and hover states
- **Dark gray text** for readability (minimum contrast ratio 4.5:1)
- **Blue** for primary actions and links
- **Green** for success states
- **Red** for delete/critical actions
- **Amber** for warnings
- **Cyan** for informational content

---

## 3. Typography

### 3.1 Font Family
- **Primary Font**: 'Segoe UI', Arial, sans-serif
- **Fallback Fonts**: Arial, Helvetica, sans-serif
- **Font Sizes**: Based on 16px base size

### 3.2 Type Scale
```css
/* Headings */
h1: 2.5rem (40px) - Page titles
h2: 2rem (32px) - Section titles
h3: 1.75rem (28px) - Subsection titles
h4: 1.5rem (24px) - Component titles
h5: 1.25rem (20px) - Label/heading
h6: 1rem (16px) - Small heading

/* Body Text */
body: 1rem (16px) - Regular text
small: 0.875rem (14px) - Smaller text
xs: 0.75rem (12px) - Extra small text

/* Font Weight */
Light: 300
Regular: 400
Medium: 500
Semi-bold: 600
Bold: 700
```

### 3.3 Line Heights
- **Headings**: 1.2
- **Body Text**: 1.5
- **Labels**: 1.4

---

## 4. Spacing & Layout

### 4.1 Spacing Scale
```css
8px   - xs (Extra small gaps)
16px  - sm (Small gaps)
24px  - md (Medium gaps)
32px  - lg (Large gaps)
40px  - xl (Extra large gaps)
48px  - xxl (Double extra large)
```

### 4.2 Container & Page Layout
- **Max Content Width**: 1400px
- **Sidebar Width**: 280px (collapsible to 60px)
- **Header Height**: 64px
- **Padding**: 24px on desktop, 16px on tablet/mobile

### 4.3 Grid System
- **Desktop**: 12-column grid with 24px gutters
- **Tablet**: 8-column grid with 20px gutters
- **Mobile**: 4-column grid with 16px gutters

---

## 5. Shared Components

### 5.1 Layout Components

#### Header/Topbar
- **Height**: 64px
- **Background**: White with subtle border
- **Content**: Logo, search bar (optional), notification icon, user profile icon
- **Sticky**: Fixed to top of page
- **Z-index**: 1000

```html
<header class="topbar">
    <div class="topbar-brand">Dream Blanks</div>
    <div class="topbar-center"><!-- Search or navigation --></div>
    <div class="topbar-right">
        <button class="icon-btn notification-btn">
            <span class="icon">🔔</span>
            <span class="badge">3</span>
        </button>
        <div class="user-profile">
            <img src="profile.jpg" alt="User" class="avatar">
            <span class="dropdown-menu">
                <a href="/profile">Profile</a>
                <a href="/settings">Settings</a>
                <a href="/logout">Logout</a>
            </span>
        </div>
    </div>
</header>
```

#### Sidebar
- **Width**: 280px (collapsible to 60px)
- **Background**: Light gray (#F5F5F5)
- **Sticky**: Fixed to left of page
- **Z-index**: 1001
- **Toggle**: Hamburger menu for mobile

```html
<aside class="sidebar">
    <nav class="nav-menu">
        <ul>
            <li>
                <a href="/dashboard" class="nav-link">
                    <span class="icon">📊</span>
                    <span class="label">Dashboard</span>
                </a>
            </li>
            <!-- More navigation items -->
        </ul>
    </nav>
</aside>
```

#### Main Content Area
- **Padding**: 24px
- **Scrollable**: Full height with overflow
- **Min-height**: Calculated from window height minus header

```html
<main class="main-content">
    <div class="container">
        <!-- Page content -->
    </div>
</main>
```

### 5.2 Form Components

#### Input Fields
- **Height**: 40px
- **Padding**: 10px 12px
- **Border**: 1px solid #D0D0D0
- **Border-radius**: 4px
- **Font-size**: 1rem
- **Disabled State**: Light gray background

```html
<div class="form-group">
    <label for="email" class="form-label">Email Address</label>
    <input 
        type="email" 
        id="email" 
        class="form-input" 
        placeholder="Enter email"
        required
    >
    <span class="form-error">Email is required</span>
</div>
```

#### Select Dropdown
- **Height**: 40px
- **Styling**: Same as input fields
- **Custom Design**: Style custom dropdown for consistency

```html
<div class="form-group">
    <label for="category" class="form-label">Category</label>
    <select id="category" class="form-select">
        <option value="">Select Category</option>
        <option value="1">Category 1</option>
        <option value="2">Category 2</option>
    </select>
</div>
```

#### Checkbox & Radio
- **Size**: 18x18px
- **Margin**: 8px right
- **Color**: Primary blue when checked

```html
<div class="form-check">
    <input type="checkbox" id="remember" class="form-check-input">
    <label for="remember" class="form-check-label">Remember me</label>
</div>
```

#### Textarea
- **Min-height**: 120px
- **Padding**: 10px 12px
- **Border**: Same as input fields
- **Resize**: Vertical only

```html
<div class="form-group">
    <label for="notes" class="form-label">Notes</label>
    <textarea id="notes" class="form-textarea" rows="4"></textarea>
</div>
```

### 5.3 Button Components

#### Button Types & States
```html
<!-- Primary Button -->
<button class="btn btn-primary">Save Changes</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">Cancel</button>

<!-- Danger Button -->
<button class="btn btn-danger">Delete</button>

<!-- Success Button -->
<button class="btn btn-success">Approve</button>

<!-- Button States -->
<button class="btn btn-primary" disabled>Disabled</button>
<button class="btn btn-primary is-loading">
    <span class="loader"></span>
    Loading...
</button>
```

#### Button Specifications
- **Height**: 40px
- **Padding**: 10px 20px
- **Border-radius**: 4px
- **Font-weight**: 600
- **Cursor**: Pointer
- **Transition**: All 0.3s ease

### 5.4 Table Components

#### Standard Table
- **Width**: 100%
- **Border-collapse**: collapse
- **Header Background**: #F5F5F5
- **Row Hover**: Light gray background

```html
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th class="sortable">
                    Product Name
                    <span class="sort-icon">⬍</span>
                </th>
                <th class="sortable">Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Product 1</td>
                <td>Category A</td>
                <td>₱1,500.00</td>
                <td><span class="badge badge-success">In Stock</span></td>
                <td>
                    <button class="icon-btn">Edit</button>
                    <button class="icon-btn">Delete</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

#### Pagination
```html
<div class="pagination">
    <button class="page-link" disabled>← Previous</button>
    <button class="page-link active">1</button>
    <button class="page-link">2</button>
    <button class="page-link">3</button>
    <button class="page-link">Next →</button>
</div>
```

### 5.5 Card Components

```html
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Card Title</h3>
    </div>
    <div class="card-body">
        <!-- Card content -->
    </div>
    <div class="card-footer">
        <!-- Card actions -->
    </div>
</div>
```

#### Card Styles
- **Background**: White
- **Border**: 1px solid #E8E8E8
- **Border-radius**: 6px
- **Box-shadow**: 0 1px 3px rgba(0,0,0,0.1)
- **Padding**: 24px

### 5.6 Modal/Dialog

```html
<div class="modal" id="exampleModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Modal Title</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Modal content -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary">Cancel</button>
            <button class="btn btn-primary">Confirm</button>
        </div>
    </div>
</div>
```

#### Modal Specifications
- **Overlay**: Semi-transparent dark background (rgba(0,0,0,0.5))
- **Content**: White with 6px border-radius
- **Max-width**: 600px (adjustable)
- **Z-index**: 2000
- **Animation**: Fade in 0.3s

### 5.7 Toast Notifications

```html
<div class="toast toast-success" id="successToast">
    <span class="toast-icon">✓</span>
    <span class="toast-message">Operation completed successfully!</span>
    <button class="toast-close">&times;</button>
</div>
```

#### Toast Types
- **Success**: Green background (#28A745)
- **Error**: Red background (#DC3545)
- **Warning**: Amber background (#FFC107)
- **Info**: Blue background (#0056B3)

#### Toast Specifications
- **Position**: Bottom-right corner
- **Width**: 400px
- **Padding**: 16px 20px
- **Duration**: Auto-dismiss after 5 seconds
- **Z-index**: 9999

### 5.8 Badge Components

```html
<span class="badge badge-success">Active</span>
<span class="badge badge-danger">Inactive</span>
<span class="badge badge-warning">Pending</span>
<span class="badge badge-info">Draft</span>
```

#### Badge Specifications
- **Padding**: 4px 8px
- **Border-radius**: 20px
- **Font-size**: 0.75rem
- **Font-weight**: 600
- **Display**: Inline-block

### 5.9 Status Indicator

```html
<!-- Dot indicator -->
<span class="status-dot status-active"></span>
<span class="status-dot status-inactive"></span>

<!-- Badge with icon -->
<span class="status-badge status-in-stock">In Stock</span>
<span class="status-badge status-low-stock">Low Stock</span>
```

### 5.10 Alert/Warning Box

```html
<div class="alert alert-info">
    <span class="alert-icon">ℹ</span>
    <div class="alert-content">
        <h4 class="alert-title">Information</h4>
        <p class="alert-message">This is an informational message.</p>
    </div>
    <button class="alert-close">&times;</button>
</div>
```

#### Alert Types
- **Info**: Blue (#17A2B8)
- **Success**: Green (#28A745)
- **Warning**: Amber (#FFC107)
- **Danger**: Red (#DC3545)

---

## 6. Icon System

### 6.1 Icon Usage
- **Size**: 20x20px (standard), 24x24px (large), 16x16px (small)
- **Color**: Inherit from text or specified color
- **Library**: Font Awesome (optional) or custom SVG icons

### 6.2 Common Icons
- Dashboard: 📊
- Products: 📦
- Clients: 👥
- Invoices: 📄
- Settings: ⚙️
- Logout: 🚪
- Edit: ✏️
- Delete: 🗑️
- Search: 🔍
- Menu: ☰
- Close: ✕
- Back: ←
- Forward: →

---

## 7. Responsive Design

### 7.1 Breakpoints
```css
@media (max-width: 768px) {
    /* Tablet/Mobile styles */
    .sidebar { width: 60px; }
    .main-content { margin-left: 60px; }
}

@media (max-width: 480px) {
    /* Mobile-only styles */
    .sidebar { display: none; }
    .main-content { margin-left: 0; }
}
```

### 7.2 Responsive Behavior
- **Desktop (1200px+)**: Full sidebar, full content
- **Tablet (769px - 1199px)**: Collapsible sidebar, adjusted grid
- **Mobile (480px - 768px)**: Hidden sidebar, 4-column grid
- **Mobile Small (<480px)**: 2-column grid, larger spacing

---

## 8. Forms & Validation

### 8.1 Form Layout
- **Two-column layout** on desktop
- **Single-column layout** on tablet/mobile
- **Consistent spacing** between form groups (16px)

### 8.2 Error States
```html
<div class="form-group is-invalid">
    <label class="form-label">Email</label>
    <input type="email" class="form-input is-invalid">
    <span class="form-error">Invalid email format</span>
</div>
```

### 8.3 Validation Rules Display
- **Real-time validation**: Show as user types
- **Error messages**: Appear below the input field
- **Error color**: Red (#DC3545)
- **Success checkmark**: Green (#28A745)

---

## 9. Data Visualization

### 9.1 Chart Types
- **Line Chart**: For trends over time
- **Bar Chart**: For comparisons
- **Pie Chart**: For proportions
- **Area Chart**: For cumulative metrics

### 9.2 Chart Colors
- **Primary**: Use brand colors
- **Gradient**: Use color transitions
- **Accessibility**: Ensure sufficient contrast

### 9.3 Legend & Labels
- **Legend Position**: Right or bottom
- **Font Size**: 12px
- **Padding**: 12px from chart

---

## 10. Accessibility

### 10.1 WCAG Compliance
- **Color Contrast**: Minimum 4.5:1 for text
- **Focus Indicators**: Visible focus states on all interactive elements
- **Semantic HTML**: Use proper heading hierarchy
- **Alt Text**: All images have descriptive alt text
- **ARIA Labels**: Form labels associated with inputs

### 10.2 Keyboard Navigation
- **Tab Order**: Logical tab sequence through page
- **Skip Links**: Skip to main content link
- **Keyboard Shortcuts**: Documented shortcuts for power users
- **Escape Key**: Close modals and dropdowns

---

## 11. Animation & Transitions

### 11.1 Standard Transitions
- **Duration**: 0.3s for UI elements
- **Timing Function**: ease-in-out
- **Properties**: opacity, transform, color

```css
.btn {
    transition: all 0.3s ease-in-out;
}

.btn:hover {
    background-color: darker;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
```

### 11.2 Loading States
- **Spinner Animation**: Rotating circular loader
- **Duration**: Smooth 1s rotation
- **Color**: Primary blue

### 11.3 Modal Transitions
- **Fade In**: 0.3s opacity increase
- **Scale**: Slight scale from 0.9 to 1
- **Combined Effect**: Simultaneous fade and scale

---

## 12. Component Usage Examples

### Example: Login Form
```html
<div class="container login-container">
    <div class="card login-card">
        <div class="card-body">
            <h2 class="card-title">Dream Blanks POS</h2>
            <form class="login-form">
                <div class="form-group">
                    <label class="form-label">Email or Username</label>
                    <input type="text" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-input" required>
                </div>
                <button class="btn btn-primary btn-block">Sign In</button>
                <a href="/forgot-password" class="forgot-link">Forgot password?</a>
            </form>
        </div>
    </div>
</div>
```

### Example: Dashboard Card
```html
<div class="stats-card">
    <div class="stats-header">
        <h4 class="stats-title">Total Sales</h4>
        <span class="stats-icon">📊</span>
    </div>
    <div class="stats-body">
        <p class="stats-value">₱125,450.00</p>
        <p class="stats-change positive">+12.5% from last week</p>
    </div>
</div>
```

---

## 13. Dark Mode (Optional Future Enhancement)
- **Dark Background**: #1E1E1E
- **Dark Surface**: #2D2D2D
- **Light Text**: #E8E8E8
- **Toggle**: Switch in user settings

---

**Document Version**: 1.0 | **Last Updated**: May 2026
