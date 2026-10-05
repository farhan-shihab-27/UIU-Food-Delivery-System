/*
 * ==========================================================
 *  UIU Food Delivery System — Shared JavaScript File
 *  File: js/uiu-food.js
 *  Author: Group Project — JavaScript Implementation
 *
 *  SYLLABUS COMPLIANCE:
 *  - Variables : let, const (camelCase)
 *  - DOM       : document.getElementById() ONLY
 *  - Events    : inline onclick="fn()" on HTML elements
 *  - Selection : getElementById ONLY (no querySelector)
 *  - Loops     : classic for (let i = 0; i < arr.length; i++)
 *  - Arrays    : [], .push(), .pop(), .length
 *  - Functions : standard function myFn(a, b) { }
 *  - Output    : .textContent for plain text, .innerHTML for <br> logs
 *  - Checks    : isNaN(), parseFloat(), parseInt()
 *  - Null check: every getElementById call at page-load level
 *                is guarded with  if (element !== null)
 *
 *  PHP & MySQL integration points are marked with:
 *  // PHP & MySQL integration point
 * ==========================================================
 */


/* =========================================================
   PAGE 1 — login.html
   Functions: validateLogin()
   ========================================================= */

function validateLogin() {
    /* Read input values */
    let email    = document.getElementById("loginEmail").value.trim();
    let password = document.getElementById("loginPassword").value;
    let feedback = document.getElementById("loginFeedback");

    /* Clear previous feedback */
    feedback.textContent = "";
    feedback.style.color = "#e74c3c";

    /* Check for empty fields */
    if (email === "" || password === "") {
        feedback.textContent = "Please fill in both email and password.";
        return;
    }

    /* Basic email format check (must contain @ and a dot after @) */
    if (email.indexOf("@") === -1 || email.indexOf(".") === -1) {
        feedback.textContent = "Please enter a valid email address.";
        return;
    }

    /* Password length check */
    if (password.length < 6) {
        feedback.textContent = "Password must be at least 6 characters.";
        return;
    }

    /* All checks passed */
    feedback.style.color = "#1f7a3e";
    feedback.textContent = "Logging in... Please wait.";

    /* Redirect based on role derived from the email prefix.
       Admins/shops/runners use their own dashboards; everyone else
       lands on the student dashboard. */
    let target = "student-dashboard.html";
    let lowerEmail = email.toLowerCase();

    if (lowerEmail.indexOf("admin") === 0) {
        target = "admin-dashboard.html";
    } else if (lowerEmail.indexOf("shop") === 0) {
        target = "shop-dashboard.html";
    } else if (lowerEmail.indexOf("runner") === 0) {
        target = "runner-dashboard.html";
    }

    /* Small delay so the user sees the feedback message before redirect */
    setTimeout(function () {
        window.location.href = target;
    }, 600);

    /* PHP & MySQL integration point:
       POST email + password to login.php.
       PHP verifies the hashed password and sets $_SESSION['role'],
       then header('Location: ' . $dashboardForRole); */
}


/* =========================================================
   PAGE 2 — register.html
   Functions: checkPasswordStrength(), validateRegister()
   ========================================================= */

function checkPasswordStrength() {
    let password = document.getElementById("reg-password").value;
    let strengthEl = document.getElementById("pwStrengthText");

    /* Reset */
    if (password.length === 0) {
        strengthEl.textContent = "";
        return;
    }

    /* Score out of 4 using if / else if */
    let score = 0;
    if (password.length >= 8)  { score = score + 1; }
    if (password.length >= 12) { score = score + 1; }
    if (password.indexOf("1") !== -1 || password.indexOf("2") !== -1 ||
        password.indexOf("3") !== -1 || password.indexOf("0") !== -1) {
        score = score + 1;
    }
    if (password.indexOf("!") !== -1 || password.indexOf("@") !== -1 ||
        password.indexOf("#") !== -1 || password.indexOf("$") !== -1) {
        score = score + 1;
    }

    /* Display strength */
    if (score <= 1) {
        strengthEl.textContent = "Strength: Weak \uD83D\uDD34";
        strengthEl.style.color = "#e74c3c";
    } else if (score === 2) {
        strengthEl.textContent = "Strength: Fair \uD83D\uDFE1";
        strengthEl.style.color = "#e67e22";
    } else if (score === 3) {
        strengthEl.textContent = "Strength: Good \uD83D\uDFE2";
        strengthEl.style.color = "#27ae60";
    } else {
        strengthEl.textContent = "Strength: Strong \uD83D\uDFE2\u2605";
        strengthEl.style.color = "#1f7a3e";
    }
}

function validateRegister() {
    let name     = document.getElementById("reg-name").value;
    let studentId = document.getElementById("reg-student-id").value;
    let email    = document.getElementById("reg-email").value;
    let phone    = document.getElementById("reg-phone").value;
    let password = document.getElementById("reg-password").value;
    let confirm  = document.getElementById("reg-confirm").value;
    let terms    = document.getElementById("terms");
    let feedback = document.getElementById("registerFeedback");

    feedback.style.color = "#e74c3c";

    /* Empty field checks */
    if (name === "") {
        feedback.textContent = "Full Name is required.";
        return;
    }
    if (studentId === "") {
        feedback.textContent = "Student ID is required.";
        return;
    }
    if (email === "") {
        feedback.textContent = "Email address is required.";
        return;
    }
    if (email.indexOf("@") === -1) {
        feedback.textContent = "Please enter a valid email address.";
        return;
    }
    if (phone === "") {
        feedback.textContent = "Phone number is required.";
        return;
    }
    if (password === "") {
        feedback.textContent = "Password is required.";
        return;
    }
    if (password.length < 8) {
        feedback.textContent = "Password must be at least 8 characters long.";
        return;
    }
    if (password !== confirm) {
        feedback.textContent = "Passwords do not match. Please re-enter.";
        return;
    }
    if (terms.checked === false) {
        feedback.textContent = "You must accept the Terms & Conditions.";
        return;
    }

    /* All validations passed */
    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 Account created successfully! Redirecting to login...";

    /* PHP & MySQL integration point:
       Change <form action="register.php" method="post"> and call form.submit().
       Example: document.getElementById("registerForm").submit(); */
}


/* =========================================================
   PAGE 3 — profile.html
   Functions: saveProfile(), changePassword()
   ========================================================= */

function saveProfile() {
    let name    = document.getElementById("fullname").value;
    let phone   = document.getElementById("phone").value;
    let address = document.getElementById("address").value;
    let feedback = document.getElementById("profileFeedback");

    feedback.style.color = "#e74c3c";

    if (name === "") {
        feedback.textContent = "Full Name cannot be empty.";
        return;
    }
    if (phone === "") {
        feedback.textContent = "Phone number cannot be empty.";
        return;
    }
    if (address === "") {
        feedback.textContent = "Delivery address cannot be empty.";
        return;
    }

    /* Success */
    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 Profile saved successfully!";

    /* PHP & MySQL integration point:
       Change <form action="save_profile.php" method="post"> and submit.
       PHP will UPDATE the users table with the new name, phone, address. */
}

function changePassword() {
    let newPass     = document.getElementById("new-pass").value;
    let confirmPass = document.getElementById("confirm-pass").value;
    let feedback    = document.getElementById("passFeedback");

    feedback.style.color = "#e74c3c";

    if (newPass === "") {
        feedback.textContent = "New password cannot be empty.";
        return;
    }
    if (newPass.length < 8) {
        feedback.textContent = "New password must be at least 8 characters.";
        return;
    }
    if (newPass !== confirmPass) {
        feedback.textContent = "Passwords do not match.";
        return;
    }

    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 Password updated successfully!";

    /* Clear fields after success */
    document.getElementById("new-pass").value     = "";
    document.getElementById("confirm-pass").value = "";

    /* PHP & MySQL integration point:
       POST new password (hashed with password_hash()) to change_password.php
       and UPDATE users table WHERE user_id = $_SESSION['user_id']. */
}


/* =========================================================
   PAGE 4 — restaurant-menu.html
   Cart: addToCart(name, price, qtyId)
   Arrays + for loop + innerHTML — professor's exact pattern
   ========================================================= */

/* Cart state variables */
let cartItems       = [];
let cartTotalAmount = 0;
const DELIVERY_FEE  = 1.50;

function addToCart(name, price, qtyId) {
    /* Read quantity input */
    let qtyStr = document.getElementById(qtyId).value;
    let qty    = parseInt(qtyStr);

    /* Validate quantity */
    if (isNaN(qty) || qty <= 0) {
        document.getElementById("cartFeedback").textContent = "Please enter a valid quantity (minimum 1).";
        document.getElementById("cartFeedback").style.color = "#e74c3c";
        return;
    }

    /* Calculate subtotal */
    let subtotal = price * qty;
    cartTotalAmount += subtotal;

    /* Push item string to array — .push() */
    cartItems.push(qty + "x " + name + " — $" + subtotal.toFixed(2));

    /* Render cart log using for loop + innerHTML */
    let logHtml = "";
    for (let i = 0; i < cartItems.length; i++) {
        logHtml += "<span style='display:block; padding: 4px 0; border-bottom: 1px solid #eee;'>"
                 + cartItems[i] + "</span>";
    }
    document.getElementById("cartLog").innerHTML = logHtml;

    /* Update total display */
    let grandTotal = cartTotalAmount + DELIVERY_FEE;
    document.getElementById("cartTotal").textContent = "Total: $" + grandTotal.toFixed(2)
                                                      + " (incl. $" + DELIVERY_FEE.toFixed(2) + " delivery)";
    document.getElementById("cartCount").textContent = cartItems.length + " item(s) in cart";

    /* Clear feedback */
    document.getElementById("cartFeedback").style.color = "#1f7a3e";
    document.getElementById("cartFeedback").textContent = "\u2705 " + name + " added to cart!";
}


/* =========================================================
   PAGE 5 — checkout.html
   Functions: applyPromo(), placeOrder()
   ========================================================= */

/* Starting total in BDT (matching the page display) */
let checkoutBaseTotal = 14.25;

function applyPromo() {
    let code     = document.getElementById("promoCode").value;
    let feedback = document.getElementById("promoFeedback");
    let totalEl  = document.getElementById("checkoutTotal");

    feedback.style.color = "#e74c3c";

    if (code === "") {
        feedback.textContent = "Please enter a promo code.";
        return;
    }

    /* Check valid promo codes using if / else if */
    if (code === "UIU10") {
        let discount   = checkoutBaseTotal * 0.10;
        let newTotal   = checkoutBaseTotal - discount;
        feedback.style.color = "#1f7a3e";
        feedback.textContent = "\u2705 Code UIU10 applied! 10% off — saved $" + discount.toFixed(2);
        totalEl.textContent  = "$" + newTotal.toFixed(2);
    } else if (code === "FLAT5") {
        let newTotal = checkoutBaseTotal - 5.00;
        if (newTotal < 0) { newTotal = 0; }
        feedback.style.color = "#1f7a3e";
        feedback.textContent = "\u2705 Code FLAT5 applied! $5.00 flat discount.";
        totalEl.textContent  = "$" + newTotal.toFixed(2);
    } else {
        feedback.textContent = "\u274C Invalid promo code. Try UIU10 or FLAT5.";
    }

    /* PHP & MySQL integration point:
       POST promo code to validate_promo.php — verify against promo_codes table
       and return the discount value to apply. */
}

function placeOrder() {
    let address  = document.getElementById("checkoutAddress").value;
    let feedback = document.getElementById("checkoutFeedback");

    feedback.style.color = "#e74c3c";

    if (address === "") {
        feedback.textContent = "Please enter a delivery address before placing the order.";
        return;
    }

    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 Order placed successfully! Redirecting to tracking...";

    /* PHP & MySQL integration point:
       INSERT INTO orders (user_id, address, total, status) VALUES (...)
       Then redirect: header('Location: order-success.php'); */
}


/* =========================================================
   PAGE 6 — wallet.html
   Function: addFunds()
   Array + for loop + innerHTML — professor's exact pattern
   ========================================================= */

/* Wallet state */
let walletBalance = 1240.00;
let txnHistory    = [];

function addFunds() {
    let amountStr = document.getElementById("addFundsInput").value;
    let amount    = parseFloat(amountStr);
    let feedback  = document.getElementById("walletFeedback");

    feedback.style.color = "#e74c3c";

    /* Validate amount */
    if (amountStr === "") {
        feedback.textContent = "Please enter an amount.";
        return;
    }
    if (isNaN(amount)) {
        feedback.textContent = "Invalid amount. Please enter a number.";
        return;
    }
    if (amount <= 0) {
        feedback.textContent = "Amount must be greater than zero.";
        return;
    }
    if (amount > 50000) {
        feedback.textContent = "Maximum top-up per transaction is \u09F350,000.";
        return;
    }

    /* Add to balance */
    walletBalance += amount;

    /* Push to transaction history array */
    txnHistory.push("+ \u09F3" + amount.toFixed(2) + " — Wallet Top-Up (Session)");

    /* Update balance display */
    document.getElementById("walletBalance").textContent = "\u09F3" + walletBalance.toFixed(2);

    /* Render transaction log using for loop + innerHTML */
    let logHtml = "<p style='font-weight:700; font-size:0.85rem; margin-bottom:8px; color:#1f7a3e;'>Session Transactions:</p>";
    for (let i = 0; i < txnHistory.length; i++) {
        logHtml += "<span style='display:block; color:#27ae60; padding:3px 0;'>" + txnHistory[i] + "</span>";
    }
    document.getElementById("txnLog").innerHTML = logHtml;

    /* Success feedback */
    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 \u09F3" + amount.toFixed(2) + " added to your wallet!";

    /* Clear input */
    document.getElementById("addFundsInput").value = "";

    /* PHP & MySQL integration point:
       POST amount to add_funds.php — INSERT into wallet_transactions table
       and UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ? */
}


/* =========================================================
   PAGE 7 — shop-add-item.html
   Function: validateAndAddItem()
   Array + for loop + innerHTML — professor's exact pattern
   ========================================================= */

/* Session-scoped menu item preview list */
let menuItemsPreview = [];

function validateAndAddItem() {
    let name     = document.getElementById("item-name").value;
    let priceStr = document.getElementById("item-price").value;
    let category = document.getElementById("item-category").value;
    let feedback = document.getElementById("addItemFeedback");
    let price    = parseFloat(priceStr);

    feedback.style.color = "#e74c3c";

    /* Validation checks */
    if (name === "") {
        feedback.textContent = "Item name is required.";
        return;
    }
    if (priceStr === "") {
        feedback.textContent = "Price is required.";
        return;
    }
    if (isNaN(price) || price <= 0) {
        feedback.textContent = "Please enter a valid price greater than 0.";
        return;
    }
    if (category === "") {
        feedback.textContent = "Please select a category.";
        return;
    }

    /* Push item to preview array */
    menuItemsPreview.push(name + " — $" + price.toFixed(2) + " (" + category + ")");

    /* Render preview list using for loop + innerHTML */
    let listHtml = "<p style='font-weight:700; margin-bottom:6px; color:#1f7a3e;'>Items Added This Session:</p>";
    for (let i = 0; i < menuItemsPreview.length; i++) {
        listHtml += "<span style='display:block; padding:4px 0; border-bottom:1px solid #eee;'>"
                 + (i + 1) + ". " + menuItemsPreview[i] + "</span>";
    }
    document.getElementById("itemPreviewList").innerHTML = listHtml;

    /* Success feedback */
    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 \"" + name + "\" added to preview! Total items: " + menuItemsPreview.length;

    /* Clear form fields */
    document.getElementById("item-name").value  = "";
    document.getElementById("item-price").value = "";
    document.getElementById("item-category").value = "";

    /* PHP & MySQL integration point:
       POST to add_item.php — INSERT INTO menu_items (shop_id, name, price, category, ...) VALUES (...)
       and redirect to shop-menu.php on success. */
}


/* =========================================================
   PAGE 8 — rate-order.html
   Functions: getRatingValue(), submitRating()
   Array + for loop + innerHTML — professor's exact pattern
   ========================================================= */

/* Reviews submitted in this session */
let reviewsLog = [];

function getRatingValue() {
    /* Loop from 5 down to 1 and check which radio is checked */
    for (let i = 5; i >= 1; i--) {
        let radio = document.getElementById("star" + i + "r");
        if (radio !== null && radio.checked === true) {
            return i;
        }
    }
    return 0; /* no rating selected */
}

function submitRating() {
    let rating  = getRatingValue();
    let comment = document.getElementById("reviewComment").value;
    let feedback = document.getElementById("ratingFeedback");

    feedback.style.color = "#e74c3c";

    if (rating === 0) {
        feedback.textContent = "Please select a star rating before submitting.";
        return;
    }

    /* Build feedback message using if / else if / else */
    let ratingMsg = "";
    if (rating === 5) {
        ratingMsg = "Excellent! \uD83C\uDF1F Thank you for the 5-star rating!";
    } else if (rating === 4) {
        ratingMsg = "Great! \uD83D\uDE0A We appreciate your positive feedback.";
    } else if (rating === 3) {
        ratingMsg = "Good. \uD83D\uDC4D We'll keep improving our service.";
    } else if (rating === 2) {
        ratingMsg = "Below Average. \uD83D\uDE10 We're sorry for the experience.";
    } else {
        ratingMsg = "Poor. \uD83D\uDE1E We sincerely apologize. We'll investigate this.";
    }

    /* Build review entry string */
    let reviewEntry = "Rating: " + rating + "/5 — " + ratingMsg;
    if (comment !== "") {
        reviewEntry += " | Comment: \"" + comment + "\"";
    }

    /* Push to review log array */
    reviewsLog.push(reviewEntry);

    /* Render review log using for loop + innerHTML */
    let logHtml = "<p style='font-weight:700; margin-bottom:6px;'>Submitted Reviews (Session):</p>";
    for (let i = 0; i < reviewsLog.length; i++) {
        logHtml += "<span style='display:block; padding:4px 0; border-bottom:1px solid #eee; font-size:0.85rem;'>"
                 + reviewsLog[i] + "</span>";
    }
    document.getElementById("reviewLog").innerHTML = logHtml;

    /* Show feedback message */
    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 " + ratingMsg;

    /* Clear comment */
    document.getElementById("reviewComment").value = "";

    /* PHP & MySQL integration point:
       POST to submit_rating.php — INSERT INTO order_ratings (order_id, user_id, rating, comment)
       then UPDATE runners SET avg_rating = ... WHERE runner_id = ? */
}


/* =========================================================
   PAGE 9 — chat.html
   Function: sendMessage()
   Array + for loop + innerHTML — professor's exact pattern
   ========================================================= */

/* Chat messages sent in this session */
let chatLog = [];

function sendMessage() {
    let inputEl = document.getElementById("chatInput");
    let msg     = inputEl.value;

    /* Do nothing if message is empty */
    if (msg === "") {
        return;
    }

    /* Push message to array */
    chatLog.push(msg);

    /* Build new message bubble HTML and append it */
    let messagesDiv = document.getElementById("chatMessages");
    let newBubble   = "<div class='chat-msg-row sent' style='margin-top:8px;'>"
                    + "<div><div class='chat-bubble sent'>" + msg + "</div>"
                    + "<div class='chat-bubble-time' style='text-align:right;'>Just now</div>"
                    + "</div></div>";
    messagesDiv.innerHTML = messagesDiv.innerHTML + newBubble;

    /* Clear input */
    inputEl.value = "";

    /* Scroll to bottom */
    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    /* PHP & MySQL integration point:
       POST to send_message.php — INSERT INTO messages (sender_id, receiver_id, content, sent_at)
       and use real-time polling or WebSocket for message refresh. */
}


/* =========================================================
   PAGE 10 — admin-dashboard.html
   Function: adminAction(action)
   Array + for loop + innerHTML — professor's exact pattern
   ========================================================= */

/* Admin action log for this session */
let adminActionLog = [];

function adminAction(action) {
    /* Push action to log */
    adminActionLog.push("[Admin] " + action);

    /* Render log using for loop + innerHTML */
    let logHtml = "<p style='font-weight:700; margin-bottom:6px; font-size:0.85rem;'>Admin Action Log (Session):</p>";
    for (let i = 0; i < adminActionLog.length; i++) {
        logHtml += "<span style='display:block; padding:3px 0; font-size:0.82rem; color:#1f7a3e;'>"
                 + "\u2713 " + adminActionLog[i] + "</span>";
    }
    document.getElementById("adminActionLog").innerHTML = logHtml;

    /* PHP & MySQL integration point:
       POST action to admin_action.php — INSERT into admin_logs table
       and UPDATE relevant table (shops/runners) status field. */
}

/* Approve/Reject for the dashboard quick-action tables */
function dashApproveShop(n) {
    let statusEl = document.getElementById("dashShopStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u2705 Approved";
        statusEl.style.color = "#1f7a3e";
    }
    adminAction("Approved Shop Application #" + n);
}

function dashRejectShop(n) {
    let statusEl = document.getElementById("dashShopStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u274C Rejected";
        statusEl.style.color = "#e74c3c";
    }
    adminAction("Rejected Shop Application #" + n);
}

function dashApproveRunner(n) {
    let statusEl = document.getElementById("dashRunnerStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u2705 Approved";
        statusEl.style.color = "#1f7a3e";
    }
    adminAction("Approved Runner Application #" + n);
}

function dashRejectRunner(n) {
    let statusEl = document.getElementById("dashRunnerStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u274C Rejected";
        statusEl.style.color = "#e74c3c";
    }
    adminAction("Rejected Runner Application #" + n);
}


/* =========================================================
   PAGE 11 — admin-shop-approvals.html
   Functions: approveShop(n), rejectShop(n)
   Array + for loop + innerHTML — professor's exact pattern
   ========================================================= */

/* Shop approval log */
let shopActionLog = [];

/* Shared helper — renders an array into a div via for loop */
function renderLog(divId, logArray, color) {
    let logDiv = document.getElementById(divId);
    if (logDiv === null) { return; }
    let html = "<p style='font-weight:700; margin-bottom:6px; font-size:0.85rem;'>Action Log (Session):</p>";
    for (let i = 0; i < logArray.length; i++) {
        html += "<span style='display:block; padding:3px 0; font-size:0.82rem; color:" + color + ";'>"
              + "\u2713 " + logArray[i] + "</span>";
    }
    logDiv.innerHTML = html;
}

function approveShop(n) {
    let statusEl = document.getElementById("shopStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u2705 Approved";
        statusEl.style.background = "#e8f5e9";
        statusEl.style.color      = "#2e7d32";
    }

    shopActionLog.push("Shop Application #" + n + " — Approved");
    renderLog("shopActionLog", shopActionLog, "#1f7a3e");

    /* PHP & MySQL integration point:
       POST to approve_shop.php — UPDATE shops SET status='approved' WHERE shop_id = n
       and send notification email to the applicant. */
}

function rejectShop(n) {
    let statusEl = document.getElementById("shopStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u274C Rejected";
        statusEl.style.background = "#fdecea";
        statusEl.style.color      = "#c62828";
    }

    shopActionLog.push("Shop Application #" + n + " — Rejected");
    renderLog("shopActionLog", shopActionLog, "#c62828");

    /* PHP & MySQL integration point:
       POST to reject_shop.php — UPDATE shops SET status='rejected' WHERE shop_id = n */
}


/* =========================================================
   PAGE 12 — admin-runner-approvals.html
   Functions: approveRunner(n), rejectRunner(n)
   ========================================================= */

/* Runner approval log */
let runnerActionLog = [];

function approveRunner(n) {
    let statusEl = document.getElementById("runnerStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u2705 Approved";
        statusEl.style.background = "#e8f5e9";
        statusEl.style.color      = "#2e7d32";
    }

    runnerActionLog.push("Runner Application #" + n + " — Approved");
    renderLog("runnerActionLog", runnerActionLog, "#1f7a3e");

    /* PHP & MySQL integration point:
       UPDATE runners SET status='approved' WHERE runner_id = n */
}

function rejectRunner(n) {
    let statusEl = document.getElementById("runnerStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u274C Rejected";
        statusEl.style.background = "#fdecea";
        statusEl.style.color      = "#c62828";
    }

    runnerActionLog.push("Runner Application #" + n + " — Rejected");
    renderLog("runnerActionLog", runnerActionLog, "#c62828");

    /* PHP & MySQL integration point:
       UPDATE runners SET status='rejected' WHERE runner_id = n */
}


/* =========================================================
   PAGE 13 — shop-orders.html
   Functions: acceptOrder(n), rejectOrder(n), markReady(n)
   ========================================================= */

/* Shop orders action log */
let orderActionLog = [];

function acceptOrder(n) {
    let statusEl = document.getElementById("orderStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\uD83C\uDF73 Preparing";
        statusEl.style.background = "#e3f2fd";
        statusEl.style.color      = "#1565c0";
    }

    orderActionLog.push("Order #" + n + " Accepted \u2192 Now Preparing");
    renderLog("orderActionLog", orderActionLog, "#1565c0");

    /* PHP & MySQL integration point:
       UPDATE orders SET status='preparing' WHERE order_id = n */
}

function rejectOrder(n) {
    let statusEl = document.getElementById("orderStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u274C Rejected";
        statusEl.style.background = "#fdecea";
        statusEl.style.color      = "#c62828";
    }

    orderActionLog.push("Order #" + n + " Rejected");
    renderLog("orderActionLog", orderActionLog, "#c62828");

    /* PHP & MySQL integration point:
       UPDATE orders SET status='rejected' WHERE order_id = n
       and trigger auto-refund to student wallet. */
}

function markReady(n) {
    let statusEl = document.getElementById("orderStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u2705 Ready for Pickup";
        statusEl.style.background = "#e8f5e9";
        statusEl.style.color      = "#2e7d32";
    }

    orderActionLog.push("Order #" + n + " \u2192 Ready for Pickup");
    renderLog("orderActionLog", orderActionLog, "#1f7a3e");

    /* PHP & MySQL integration point:
       UPDATE orders SET status='ready' WHERE order_id = n
       and notify the assigned runner. */
}


/* =========================================================
   PAGE 14 — shop-orders-pending.html
   Functions: acceptPending(n), declinePending(n)
   ========================================================= */

/* Pending orders action log */
let pendingActionLog = [];

function acceptPending(n) {
    let statusEl = document.getElementById("pendingStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\uD83C\uDF73 Accepted \u2192 Preparing";
        statusEl.style.background = "#e3f2fd";
        statusEl.style.color      = "#1565c0";
    }

    pendingActionLog.push("Pending Order #" + n + " Accepted \u2192 Preparing");
    renderLog("pendingActionLog", pendingActionLog, "#1565c0");

    /* PHP & MySQL integration point:
       UPDATE orders SET status='preparing' WHERE order_id = n */
}

function declinePending(n) {
    let statusEl = document.getElementById("pendingStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u274C Declined";
        statusEl.style.background = "#fdecea";
        statusEl.style.color      = "#c62828";
    }

    pendingActionLog.push("Pending Order #" + n + " Declined");
    renderLog("pendingActionLog", pendingActionLog, "#c62828");

    /* PHP & MySQL integration point:
       UPDATE orders SET status='declined' WHERE order_id = n
       and trigger refund to student wallet. */
}


/* =========================================================
   PAGE 15 — complaint-management.html
   Functions: resolveComplaint(n), closeComplaint(n)
   ========================================================= */

/* Complaint action log */
let complaintActionLog = [];

function resolveComplaint(n) {
    let statusEl = document.getElementById("complaintStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u2705 Resolved";
        statusEl.style.background = "#e8f5e9";
        statusEl.style.color      = "#2e7d32";
    }

    complaintActionLog.push("Complaint #" + n + " marked as Resolved");
    renderLog("complaintActionLog", complaintActionLog, "#1f7a3e");

    /* PHP & MySQL integration point:
       UPDATE complaints SET status='resolved' WHERE complaint_id = n */
}

function closeComplaint(n) {
    let statusEl = document.getElementById("complaintStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\uD83D\uDD12 Closed";
        statusEl.style.background = "#f3f4f6";
        statusEl.style.color      = "#6b7280";
    }

    complaintActionLog.push("Complaint #" + n + " Closed");
    renderLog("complaintActionLog", complaintActionLog, "#6b7280");

    /* PHP & MySQL integration point:
       UPDATE complaints SET status='closed' WHERE complaint_id = n */
}

function submitResolutionNote() {
    let note     = document.getElementById("resolutionNote").value;
    let feedback = document.getElementById("resolutionFeedback");

    feedback.style.color = "#e74c3c";

    if (note === "") {
        feedback.textContent = "Please enter a resolution note before saving.";
        return;
    }

    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 Resolution note saved!";

    complaintActionLog.push("Resolution note added: \"" + note + "\"");
    renderLog("complaintActionLog", complaintActionLog, "#1f7a3e");

    /* PHP & MySQL integration point:
       UPDATE complaints SET resolution_note = ? WHERE complaint_id = ? */
}


/* =========================================================
   PAGE 16 — runner-dashboard.html
   Functions: acceptJob(n), declineJob(n), markDelivered()
   ========================================================= */

/* Runner job log */
let runnerJobLog = [];

function acceptJob(n) {
    let statusEl = document.getElementById("jobStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\uD83D\uDE97 On the Way!";
        statusEl.style.color = "#1f7a3e";
        statusEl.style.fontWeight = "700";
    }

    runnerJobLog.push("Job #" + n + " Accepted \u2014 Headed to pickup!");
    renderLog("runnerJobLog", runnerJobLog, "#1f7a3e");

    /* PHP & MySQL integration point:
       UPDATE deliveries SET runner_id = ?, status='on_the_way' WHERE order_id = n */
}

function declineJob(n) {
    let statusEl = document.getElementById("jobStatus" + n);
    if (statusEl !== null) {
        statusEl.textContent = "\u274C Declined";
        statusEl.style.color = "#e74c3c";
    }

    runnerJobLog.push("Job #" + n + " Declined");
    renderLog("runnerJobLog", runnerJobLog, "#e74c3c");

    /* PHP & MySQL integration point:
       INSERT into declined_jobs and reassign order to next available runner. */
}

function markDelivered() {
    let feedback = document.getElementById("deliveryFeedback");

    feedback.style.color = "#1f7a3e";
    feedback.textContent = "\u2705 Delivery marked as complete! Earnings updated.";

    runnerJobLog.push("Active Delivery — Marked as Delivered \u2713");
    renderLog("runnerJobLog", runnerJobLog, "#1f7a3e");

    /* PHP & MySQL integration point:
       UPDATE orders SET status='delivered', delivered_at=NOW() WHERE order_id = ?
       UPDATE runners SET total_deliveries = total_deliveries + 1 WHERE runner_id = ? */
}
