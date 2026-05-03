# Dream Blanks POS System - API Endpoints Specification

## 1. API Overview

**Base URL**: `/api/v1`
**Authentication**: Session-based (user must be logged in)
**Response Format**: JSON
**HTTP Methods**: GET, POST, PUT, DELETE
**CSRF Protection**: Required on all state-changing requests

### Response Format
```json
{
  "success": true,
  "code": 200,
  "message": "Operation successful",
  "data": { },
  "errors": null,
  "timestamp": "2026-05-01T12:00:00Z"
}
```

### Error Response Format
```json
{
  "success": false,
  "code": 400,
  "message": "Validation failed",
  "data": null,
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  },
  "timestamp": "2026-05-01T12:00:00Z"
}
```

---

## 2. Authentication Endpoints

### 2.1 User Login
**Endpoint**: `POST /api/v1/auth/login`

**Request**:
```json
{
  "username_or_email": "user@example.com",
  "password": "password123"
}
```

**Response** (Success):
```json
{
  "success": true,
  "code": 200,
  "message": "Login successful",
  "data": {
    "user_id": 1,
    "username": "user@example.com",
    "first_name": "John",
    "email": "user@example.com",
    "roles": ["Admin"]
  }
}
```

**Response** (Failure):
```json
{
  "success": false,
  "code": 401,
  "message": "Invalid credentials",
  "errors": { }
}
```

### 2.2 User Logout
**Endpoint**: `POST /api/v1/auth/logout`

**Response**:
```json
{
  "success": true,
  "code": 200,
  "message": "Logout successful"
}
```

### 2.3 Forgot Password
**Endpoint**: `POST /api/v1/auth/forgot-password`

**Request**:
```json
{
  "email": "user@example.com"
}
```

**Response**:
```json
{
  "success": true,
  "code": 200,
  "message": "OTP sent to your email"
}
```

### 2.4 Verify OTP
**Endpoint**: `POST /api/v1/auth/verify-otp`

**Request**:
```json
{
  "email": "user@example.com",
  "otp": "123456"
}
```

**Response** (Success):
```json
{
  "success": true,
  "code": 200,
  "message": "OTP verified",
  "data": {
    "reset_token": "token123"
  }
}
```

### 2.5 Reset Password
**Endpoint**: `POST /api/v1/auth/reset-password`

**Request**:
```json
{
  "reset_token": "token123",
  "new_password": "newpassword123"
}
```

**Response**:
```json
{
  "success": true,
  "code": 200,
  "message": "Password reset successful"
}
```

---

## 3. User Management Endpoints

### 3.1 List Users
**Endpoint**: `GET /api/v1/users`

**Query Parameters**:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 10)
- `search`: Search by name, email, username
- `status`: Filter by status (active, inactive)
- `sort`: Sort field (default: created_at)
- `order`: Sort order (asc, desc)

**Response**:
```json
{
  "success": true,
  "data": {
    "users": [
      {
        "id": 1,
        "username": "john.doe",
        "email": "john@example.com",
        "first_name": "John",
        "middle_name": "M",
        "last_name": "Doe",
        "status": "active",
        "roles": ["Admin", "Manager"],
        "created_at": "2026-01-01T10:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 25,
      "last_page": 3
    }
  }
}
```

### 3.2 Get User
**Endpoint**: `GET /api/v1/users/{user_id}`

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "username": "john.doe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "profile_photo": "/uploads/profiles/user1.jpg",
    "status": "active",
    "roles": ["Admin"],
    "permissions": ["users.view", "users.edit", "products.view"],
    "created_at": "2026-01-01T10:00:00Z",
    "last_login": "2026-05-01T14:30:00Z"
  }
}
```

### 3.3 Create User
**Endpoint**: `POST /api/v1/users`

**Request**:
```json
{
  "username": "jane.doe",
  "email": "jane@example.com",
  "password": "temppassword123",
  "first_name": "Jane",
  "middle_name": "M",
  "last_name": "Doe",
  "roles": [1, 2]
}
```

**Response**: (201 Created)
```json
{
  "success": true,
  "code": 201,
  "message": "User created successfully",
  "data": {
    "id": 2,
    "username": "jane.doe",
    "email": "jane@example.com"
  }
}
```

### 3.4 Update User
**Endpoint**: `PUT /api/v1/users/{user_id}`

**Request**:
```json
{
  "first_name": "Jane",
  "email": "jane.updated@example.com",
  "status": "active"
}
```

**Response**:
```json
{
  "success": true,
  "message": "User updated successfully"
}
```

### 3.5 Delete User
**Endpoint**: `DELETE /api/v1/users/{user_id}`

**Response**:
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

## 4. Product Endpoints

### 4.1 List Products
**Endpoint**: `GET /api/v1/products`

**Query Parameters**:
- `page`: Page number
- `per_page`: Items per page
- `search`: Search by name, SKU
- `category_id`: Filter by category
- `color_id`: Filter by color
- `size_id`: Filter by size
- `status`: Filter by status
- `sort`: Sort field
- `order`: Sort order

**Response**:
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "sku": "PROD001",
        "name": "White T-Shirt",
        "category": "Apparel",
        "color": "White",
        "size": "Medium",
        "cost_price": 150.00,
        "selling_price": 299.00,
        "current_stock": 50,
        "status": "active",
        "images": ["/uploads/products/1.jpg"]
      }
    ],
    "pagination": { }
  }
}
```

### 4.2 Get Product
**Endpoint**: `GET /api/v1/products/{product_id}`

### 4.3 Create Product
**Endpoint**: `POST /api/v1/products`

**Request**:
```json
{
  "sku": "PROD001",
  "name": "White T-Shirt",
  "description": "Premium white t-shirt",
  "category_id": 1,
  "color_id": 2,
  "size_id": 3,
  "cost_price": 150.00,
  "selling_price": 299.00,
  "unit_type": "piece",
  "initial_stock": 100,
  "low_stock_alert": 10
}
```

### 4.4 Update Product
**Endpoint**: `PUT /api/v1/products/{product_id}`

### 4.5 Delete Product
**Endpoint**: `DELETE /api/v1/products/{product_id}`

### 4.6 Bulk Import Products
**Endpoint**: `POST /api/v1/products/bulk-import`

**Request**: (Form data with CSV file)
```
file: products.csv
```

---

## 5. Inventory Endpoints

### 5.1 Get Inventory
**Endpoint**: `GET /api/v1/inventory`

**Response**:
```json
{
  "success": true,
  "data": {
    "inventory": [
      {
        "product_id": 1,
        "product_name": "White T-Shirt",
        "quantity_on_hand": 50,
        "stock_status": "in_stock",
        "low_stock_alert": 10
      }
    ]
  }
}
```

### 5.2 Create Restock Order
**Endpoint**: `POST /api/v1/inventory/restock`

**Request**:
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity_requested": 50
    },
    {
      "product_id": 2,
      "quantity_requested": 30
    }
  ],
  "supplier_name": "Supplier XYZ",
  "delivery_date": "2026-05-15"
}
```

**Response**:
```json
{
  "success": true,
  "code": 201,
  "message": "Restock order created",
  "data": {
    "restock_id": 5,
    "order_number": "RO-2026-0005"
  }
}
```

### 5.3 Update Restock Status
**Endpoint**: `PUT /api/v1/inventory/restock/{restock_id}`

**Request**:
```json
{
  "delivery_status": "delivered",
  "notes": "Received all items in good condition"
}
```

---

## 6. POS Endpoints

### 6.1 Get POS Products
**Endpoint**: `GET /api/v1/pos/products`

**Query Parameters**:
- `search`: Search products
- `category_id`: Filter by category
- `limit`: Number of products to return

**Response**:
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "White T-Shirt",
        "category": "Apparel",
        "color": "White",
        "size": "Medium",
        "price": 299.00,
        "stock": 50,
        "image": "/uploads/products/1.jpg"
      }
    ]
  }
}
```

### 6.2 Checkout
**Endpoint**: `POST /api/v1/pos/checkout`

**Request**:
```json
{
  "client_id": null,
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "unit_price": 299.00
    }
  ],
  "subtotal": 598.00,
  "discount_amount": 50.00,
  "tax_amount": 55.00,
  "additional_fee": 0,
  "total_amount": 603.00,
  "payment_mode": "cash",
  "payment_status": "fully_paid",
  "notes": "Order notes here"
}
```

**Response**:
```json
{
  "success": true,
  "code": 201,
  "message": "Checkout successful",
  "data": {
    "invoice_id": 10,
    "invoice_number": "INV-2026-00010",
    "total_amount": 603.00,
    "receipt_url": "/api/v1/invoices/10/print"
  }
}
```

---

## 7. Invoice Endpoints

### 7.1 List Invoices
**Endpoint**: `GET /api/v1/invoices`

**Query Parameters**:
- `page`: Page number
- `per_page`: Items per page
- `search`: Search by invoice number, client name
- `status`: Filter by payment status
- `date_from`: Filter by date range
- `date_to`: Filter by date range

### 7.2 Get Invoice
**Endpoint**: `GET /api/v1/invoices/{invoice_id}`

### 7.3 Create Invoice
**Endpoint**: `POST /api/v1/invoices`

### 7.4 Add Payment
**Endpoint**: `POST /api/v1/invoices/{invoice_id}/payments`

**Request**:
```json
{
  "payment_date": "2026-05-01",
  "payment_amount": 300.00,
  "payment_mode": "cash",
  "reference_number": "CHK001"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Payment recorded successfully",
  "data": {
    "payment_id": 1,
    "new_payment_status": "partially_paid",
    "total_paid": 300.00,
    "balance_due": 303.00
  }
}
```

### 7.5 Get Invoice for Printing
**Endpoint**: `GET /api/v1/invoices/{invoice_id}/print`

**Response**: (HTML or PDF - depends on implementation)

### 7.6 Send Invoice Email
**Endpoint**: `POST /api/v1/invoices/{invoice_id}/send-email`

**Request**:
```json
{
  "recipient_email": "customer@example.com"
}
```

---

## 8. Reports Endpoints

### 8.1 Sales Report
**Endpoint**: `GET /api/v1/reports/sales`

**Query Parameters**:
- `date_from`: Start date
- `date_to`: End date
- `format`: csv, json (default: json)

**Response**:
```json
{
  "success": true,
  "data": {
    "total_sales": 15000.00,
    "transaction_count": 45,
    "average_transaction": 333.33,
    "sales_by_mode": {
      "cash": 8000.00,
      "bdo": 4000.00,
      "gcash": 3000.00
    },
    "top_products": []
  }
}
```

### 8.2 Inventory Report
**Endpoint**: `GET /api/v1/reports/inventory`

### 8.3 Financial Report
**Endpoint**: `GET /api/v1/reports/financial`

### 8.4 Export Report
**Endpoint**: `GET /api/v1/reports/export`

**Query Parameters**:
- `type`: sales, inventory, financial, audit
- `format`: csv, pdf
- `date_from`: Start date
- `date_to`: End date

---

## 9. Dashboard Endpoints

### 9.1 Get Dashboard Metrics
**Endpoint**: `GET /api/v1/dashboard/metrics`

**Response**:
```json
{
  "success": true,
  "data": {
    "total_sales_today": 5000.00,
    "total_sales_week": 35000.00,
    "total_sales_month": 150000.00,
    "outstanding_receivables": 12500.00,
    "low_stock_items": 8,
    "pending_restocks": 3
  }
}
```

### 9.2 Get Dashboard Charts
**Endpoint**: `GET /api/v1/dashboard/charts`

**Query Parameters**:
- `period`: today, week, month, year

**Response**:
```json
{
  "success": true,
  "data": {
    "sales_trend": {
      "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      "data": [1000, 1200, 1100, 1500, 2000, 2500, 1800]
    },
    "top_products": [],
    "payment_modes": []
  }
}
```

---

## 10. Notification Endpoints

### 10.1 Get Notifications
**Endpoint**: `GET /api/v1/notifications`

**Query Parameters**:
- `page`: Page number
- `unread_only`: true/false

### 10.2 Mark Notification as Read
**Endpoint**: `PUT /api/v1/notifications/{notification_id}/read`

### 10.3 Delete Notification
**Endpoint**: `DELETE /api/v1/notifications/{notification_id}`

---

## 11. Audit Log Endpoints

### 11.1 List Audit Logs
**Endpoint**: `GET /api/v1/audit-logs`

**Query Parameters**:
- `user_id`: Filter by user
- `action_type`: Filter by action
- `module`: Filter by module
- `date_from`: Filter by date range
- `date_to`: Filter by date range

### 11.2 Export Audit Logs
**Endpoint**: `GET /api/v1/audit-logs/export`

---

## 12. Settings Endpoints

### 12.1 Get Settings
**Endpoint**: `GET /api/v1/settings`

### 12.2 Update Settings
**Endpoint**: `PUT /api/v1/settings`

**Request**:
```json
{
  "business_name": "Dream Blanks",
  "currency_symbol": "₱",
  "invoice_prefix": "INV-",
  "low_stock_alert_default": 10
}
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 204 | No Content - Request successful, no content to return |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Permission denied |
| 404 | Not Found - Resource not found |
| 409 | Conflict - Resource conflict |
| 422 | Unprocessable Entity - Validation failed |
| 500 | Internal Server Error - Server error |

---

## Rate Limiting

- **Limit**: 100 requests per minute per user
- **Headers**: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset

---

## Pagination

All list endpoints support pagination:
- `page`: Current page (default: 1)
- `per_page`: Items per page (default: 10, max: 100)
- `sort`: Sort field (default: created_at)
- `order`: asc or desc (default: desc)

---

**Document Version**: 1.0 | **Last Updated**: May 2026
