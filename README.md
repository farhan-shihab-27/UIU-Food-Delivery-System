<div align="center">

# UIU Food Delivery System

### *From campus kitchen to your classroom door — fast, trackable, and seamless.*

<br>

![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)

![Status](https://img.shields.io/badge/Status-Active-success?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)
![Made at](https://img.shields.io/badge/Made%20at-UIU-orange?style=flat-square)

</div>

---

## Project Overview

**UIU Food Delivery System** is a full-stack, role-based web platform that digitizes the food ordering experience for the university community. It connects **students, campus shops, delivery runners, and administrators** within a single, unified ecosystem.

> **The Problem:** Ordering food on campus is fragmented — long queues, no visibility into order status, manual coordination between shops and delivery personnel, and no centralized oversight.
>
> **The Solution:** A structured platform where customers browse dynamic menus and check out securely, shops manage their listings, runners fulfill deliveries, and admins govern the entire workflow through approval pipelines — all with **real-time order tracking** from kitchen to doorstep.

---

## Key Features

### Customer
- **Secure Authentication** — Registration and login with session management
- **Dynamic Restaurant Menus** — Menus rendered in real time from the database
- **Interactive Cart System** — Add, remove, and update quantities instantly without page reloads
- **Secure Checkout** — Validated order placement with server-side verification
- **Real-Time Order Tracking** — Live status updates from preparation to delivery

### Shop Owner
- **Shop Registration & Approval Workflow** — Onboarding gated by admin verification
- **Menu Management** — Create, update, and remove food items and pricing
- **Incoming Order Handling** — Accept, prepare, and update order status

### Runner
- **Runner Application & Approval Workflow** — Verified onboarding through admin review
- **Delivery Assignment** — View and accept available delivery requests
- **Status Updates** — Update delivery progress to reflect in the customer's tracker

### Admin
- **Centralized Dashboard** — System-wide overview of users, shops, runners, and orders
- **Approval Management** — Approve or reject shop and runner applications
- **User & Platform Oversight** — Monitor activity and maintain platform integrity

---

## Technology Stack

| Layer | Technology | Purpose |
|:------|:-----------|:--------|
| **Markup** | HTML5 | Semantic page structure and content layout |
| **Styling** | CSS3 | Responsive design, layout, and visual identity |
| **Client Logic** | JavaScript (ES6+) | DOM manipulation, cart logic, and frontend interactivity |
| **Server-Side** | PHP | Backend processing, authentication, and business logic |
| **Database** | MySQL | Relational storage for users, menus, orders, and approvals |
| **Environment** | XAMPP (Apache + MySQL) | Local development server and database management |

---

## Team Contributions

| Member | Role | Module Ownership |
|:-------|:-----|:-----------------|
| **Farhan** | Frontend Logic Developer | Core JavaScript logic, DOM manipulation & frontend interactivity |
| **Wazid** | Frontend Designer | Frontend Design (HTML/CSS) |
| **Istiaq** | Frontend Designer | Frontend Design (HTML/CSS) |
| **Shabab** | Backend & Database Developer | PHP backend integration & MySQL database architecture |

---

## Local Setup & Installation Guide

### 📋 Prerequisites

- [XAMPP](https://www.apachefriends.org/) (PHP 7.4+ recommended)
- [Git](https://git-scm.com/)
- A modern web browser (Chrome, Firefox, Edge)

### 1️. Clone the Repository

```bash
git clone https://github.com/<your-username>/uiu-food-delivery-system.git
cd uiu-food-delivery-system
```

### 2️. Set Up XAMPP

1. Install and launch the **XAMPP Control Panel**.
2. Click **Start** next to both **Apache** and **MySQL**. Both should turn green.
3. Move (or copy) the project folder into XAMPP's `htdocs` directory:

   | OS | Destination Path |
   |:---|:-----------------|
   | **Windows** | `C:\xampp\htdocs\uiu-food-delivery-system` |
   | **macOS** | `/Applications/XAMPP/htdocs/uiu-food-delivery-system` |
   | **Linux** | `/opt/lampp/htdocs/uiu-food-delivery-system` |

> **Tip:** You can also clone the repository directly inside `htdocs` to skip this step.

### 3️. Configure the Database

1. Open your browser and go to **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**.
2. Click **New** in the left sidebar and create a database named:

   ```
   uiu_food_delivery
   ```

   *(Use `utf8mb4_general_ci` as the collation.)*
3. Select the new database, then open the **Import** tab.
4. Click **Choose File** and select the `.sql` file from the project's `database/` folder.
5. Scroll down and click **Go**. Confirm the success message and verify the tables appear.

### 4️. Update Database Credentials *(if necessary)*

Open the database connection file (typically `php/db_connect.php` or `php/config.php`) and make sure the values match your local setup:

```php
<?php
$host     = "localhost";
$username = "root";      // XAMPP default
$password = "";          // XAMPP default is empty
$database = "uiu_food_delivery";   // Must match the database you created
?>
```

> ⚠️ If the database name you created differs from the one above, update `$database` accordingly.

### 5. Run the Application

Open your browser and navigate to:

```
http://localhost/uiu-food-delivery-system/
```

### 🛠️ Troubleshooting

| Issue | Solution |
|:------|:---------|
| **Apache won't start** | Port 80 is likely in use (Skype, IIS). Change Apache's port in `httpd.conf` or stop the conflicting service. |
| **MySQL won't start** | Port 3306 may be occupied. Stop other MySQL services or change the port in `my.ini`. |
| **"Connection failed" error** | Verify credentials in the DB config file and confirm MySQL is running. |
| **Blank page / 404** | Confirm the project folder name in `htdocs` matches the URL you are visiting. |

---

## 📁 Repository Structure

```text
uiu-food-delivery-system/
│
├── 📂 css/                  # Stylesheets (layout, components, themes)
├── 📂 js/                   # Client-side logic (cart, DOM handling, interactivity)
├── 📂 php/                  # Backend scripts (auth, orders, approvals, DB connection)
├── 📂 database/             # SQL schema & seed data (.sql)
├── 📂 images/               # Static assets (logos, food images, icons)
├── 📂 admin/                # Admin dashboard pages
├── 📂 shop/                 # Shop owner pages
├── 📂 runner/               # Runner pages
│
├── 📄 index.html            # Landing page
├── 📄 login.html            # Authentication - Login
├── 📄 signup.html           # Authentication - Registration
└── 📄 README.md             # Project documentation
```

> *Folder names reflect the project's modular organization. Adjust to match your actual repository layout.*

---

## Contributing

1. **Fork** the repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Commit your changes: `git commit -m "Add: your feature description"`
4. Push to the branch: `git push origin feature/your-feature-name`
5. Open a **Pull Request**

---

## License

This project is licensed under the **MIT License**. See the `LICENSE` file for details.

---

<div align="center">

### If you found this project useful, consider giving it a star!

**Built with by Farhan, Wazid, Istiaq & Shabab**

</div>
````
