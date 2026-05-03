okay so this is the features i want to implement in the system:

> system architecture and design ui
tech stack: PHP vanilla + MySQL (to be hosted on PHP hostinger shared server)
ui and styling: gray and white theme, simple minimalist design but highly professional and modern
modular component based (shared component) OOP oriented
have toast notification
have sidebar collapsible and topbar (notification icon and user profile icon with dropdown)
table is paginated
search functionality
download buttons to download tables
add and edit modals etc.
confirmation modals for actions needing confirmation
tables headers for sorting
have date filters etc
use proper charts, appropriate charts, best and professional cards, etc. 


these are the following features:

> roles and permission:
can assign permission on roles (view, add, edit and delete) and roles will be assigned to users

> user management:
  = users will have profiles, name (first, middle, last), email, username

> login only no register
  = can login using email or username then password. have forgot password feature to email otp in their email and when verified, it will prompt to reset password.

> client management
  = clients will have profile(optional), name (first,middle,last), address(optional), contact#(optional) - then can add up to 3 addresses and can add up to 5 contact#

> product management
  = products will have image(optional), product name (req), category (category will be from database - optional), color (from database - optional), size(from database - optional), cost price (req), selling price (req), initial stock, unit (piece default), description (optional), status (active default), low stock alert # (optional)

> product variation management
  = categories will have only name, description (optional), status (active default)
  = color will have only name, status (active default), 
  = size will have only name, status (active default)

> inventory management
  = inventory will have: product (name, category, color, size), price (selling), quantity, stock status
  = have restock to restock selected products and will enter how many each for the restock. will be recorded who user restock. have order date, delivery date, product (name, category, color, size), quantity, restock quantity, supplier, delivery status (ordered, delivered, incomplete, problematic - default ordered), days to deliver (based on order date and delivery date), notes (optional)

> POS point of sale page.
  = it will have mostly the picture of product, product (name, category, color, size), and price. whole card is clickable to add to cart at the right side. it will have: client dropdown (optional to pick client), then at the bottom is discount (optional), tax (optional), additional fee (optional), notes (optional). when checkout, it will generate a receipt invoice okay exactly like the pasted image. can also have partial payment or unpaid but default is full payment. mode of payment (cash, bdo, gcash)

> invoice generator
  = this is the invoice generator in the POS. see pasted image for the format and layout of the receipt. I want exactly like that mirrored copy. this invoice generator can edit the FORMAT and LAYOUT of t he receipt invoice. so those can be formatted and layout can be edited so what format and layout in this will be the format and layout in that POS.
  = invoice # auto generated. format can be edited in app settings.

> invoice tracking 
  = invoice tracking will have: invoice #, invoice date, customer name, invoice sent (sent / not sent - default sent), total amount, total paid, invoice status (fully paid, partially paid, unpaid), mode of payment (cash, bdo, gcash), payment 1 (date and amount) can add up to as many payments in order to complete the transaction.

> transaction logs
  = ALL transactions (invoice, sales, expenses) okay? this is logs only

> audit logs
  = all actions that the user will do should be logged from (adding, editing, deleting, login and logout)

> reports page
  = can generate report charts, report tables, report stats cards, everything and can download reports into csv files. 

> dashboard
  = everything appropriate dashboard, appropriate chart, appropriate stats cards. understood?

> notifications module, ALL events that need notification should have notification.
