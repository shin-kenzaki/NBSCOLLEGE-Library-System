# Library System

This project is a Library Management System designed to facilitate the management of book reservations and user interactions within a library setting. The system allows users to search for books, reserve them, and view their reservation history.

## Project Structure

```
Library-System
├── User
│   ├── inc
│   │   ├── header.php        # Contains the HTML header and navigation elements for the user interface.
│   │   └── footer.php        # Contains the HTML footer for the user interface.
│   ├── cancelled_reservations.php  # Displays the cancelled reservations for the logged-in user.
│   ├── checkout.php          # Handles the checkout process for book reservations.
│   ├── reservation_history.php      # Displays all reservation items of the user that have either a cancel date or a received date.
├── db.php                    # Contains the database connection logic.
└── README.md                 # Documentation for the project.
```

## Features

- **User Interface**: A user-friendly interface that allows users to navigate through various functionalities such as searching for books, viewing their cart, and checking their reservation history.
- **Book Reservations**: Users can reserve books and view their current reservations.
- **Cancelled Reservations**: Users can view their cancelled reservations.
- **Reservation History**: Users can view all their reservation items that have either a cancel date or a received date.

## Setup Instructions

1. **Clone the Repository**: Clone this repository to your local machine using:
   ```
   git clone <repository-url>
   ```

2. **Set Up the Database**: Create a database in your preferred database management system and import the necessary SQL scripts to set up the required tables.

3. **Configure Database Connection**: Update the `db.php` file with your database connection details.

4. **Run the Application**: Use a local server environment (like XAMPP or WAMP) to run the application. Place the project folder in the server's root directory (e.g., `htdocs` for XAMPP).

5. **Access the Application**: Open your web browser and navigate to `http://localhost/Library-System/User/` to access the user interface.

## Contributing

Contributions are welcome! Please feel free to submit a pull request or open an issue for any enhancements or bug fixes.