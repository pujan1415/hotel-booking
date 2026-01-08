hotel-booking-system/
│
├── index.php
├── hotel_detail.php
├── booking.php
├── booking_success.php
├── .htaccess
│
├── config/
│   ├── db.php
│   └── config.php
│
├── helpers/
│   ├── sanitize.php
│   ├── upload.php
│   └── redirect.php
│
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── user_auth_check.php
│
├── user/
│   ├── dashboard.php
│   ├── my_bookings.php
│   └── profile.php
│
├── admin/
│   ├── dashboard.php
│   │
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── auth_check.php
│   │
│   ├── hotel/
│   │   ├── index.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── delete.php
│   │
│   ├── room/
│   │   ├── index.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── delete.php
│   │
│   ├── bookings/
│   │   ├── index.php
│   │   ├── view.php
│   │   └── update_status.php
│   │
│   ├── user/
│   │   ├── index.php
│   │   ├── view.php
│   │   └── block.php
│   │
│   └── includes/
│       ├── header.php
│       ├── footer.php
│       └── sidebar.php
│
├── includes/                # FRONTEND
│   ├── header.php
│   ├── footer.php
│   └── navbar.php
│
├── ajax/
│   ├── search_hotels.php
│   ├── check_availability.php
│   └── book_room.php
│
├── payment/
│   ├── esewa.php
│   ├── khalti.php
│   └── success.php
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── uploads/
│   ├── hotels/
│   └── rooms/
│
├── logs/
│   └── error.log
│
└── database/
    └── hotel_booking.sql
