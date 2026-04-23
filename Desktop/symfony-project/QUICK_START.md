# Authentication Integration Guide

## Quick Start

Your Symfony login and register system is now live! The development server is running on **http://localhost:8000**

### Available Routes:
- **Home** → http://localhost:8000/
- **Login** → http://localhost:8000/login
- **Register** → http://localhost:8000/register
- **Logout** → http://localhost:8000/logout

---

## Testing the System

### Test Login with Existing User

Your database already has existing users. To test login:

1. Go to http://localhost:8000/login
2. Use any email from the `user` table:
   - `test@gmail.com`
   - `eyamhadhbi@gmail.com`
   - `ahmedrhouma24@gmail.com`
   - etc.

3. For password, you need to hash a test password. Use:
   ```bash
   cd c:\Users\User\Desktop\symfony-project
   php bin/console security:hash-password
   ```
   Enter a password (e.g., `password123`) and copy the hash.

4. Update a user in database with the hash:
   ```bash
   php bin/console doctrine:query:sql "UPDATE user SET password='<paste-hash-here>' WHERE id=1"
   ```

### Test Registration

1. Go to http://localhost:8000/register
2. Fill in the form:
   - First Name: John
   - Last Name: Doe
   - Phone: 123456789
   - Date of Birth: 2000-01-01
   - Email: john.doe@example.com
   - Account Type: Patient
   - Password: password123

3. Click Register
4. You'll be redirected to login
5. Use your new email and password to login

---

## User Types & Roles

The system automatically assigns roles based on the `type` field:

| Type | Database Value | Symfony Role | Permissions |
|------|---|---|---|
| Patient | `Patient` | `ROLE_USER` | User access |
| Psychologist | `Psychologist` | `ROLE_PSYCHOLOGIST` | Therapist access |
| Admin | `Admin` | `ROLE_ADMIN` | Full access |

---

## Database Connection

Connected to your existing `mentis` database:
- **Host:** 127.0.0.1
- **Port:** 3306
- **Database:** mentis
- **User:** root
- **Engine:** MariaDB 10.4.32

All tables from your export are available:
- assessment
- assessmentresult
- content_node
- content_path
- events
- event_registrations
- goal
- mood
- question
- sessions
- session_review
- user
- user_old

---

## File Structure

```
symfony-project/
├── src/
│   ├── Controller/
│   │   ├── AuthController.php          (Login, Logout, Home)
│   │   └── RegistrationController.php  (Register)
│   ├── Entity/
│   │   └── User.php                    (User entity)
│   ├── Form/
│   │   └── RegistrationFormType.php    (Registration form)
│   └── Repository/
│       └── UserRepository.php          (User queries)
├── templates/
│   ├── base.html.twig                  (Base layout)
│   ├── home.html.twig                  (Home page)
│   ├── auth/
│   │   └── login.html.twig             (Login form)
│   └── registration/
│       └── register.html.twig          (Registration form)
├── config/
│   └── packages/
│       └── security.yaml               (Security config)
└── public/
    └── index.php                        (Application entry point)
```

---

## Customization Guide

### Change Login Redirect
Edit `config/packages/security.yaml`:
```yaml
form_login:
    login_path: app_login
    check_path: app_login
    # Add after check_path:
    default_target_path: app_dashboard  # Redirect after login
```

### Add Dashboard Route
Create `src/Controller/DashboardController.php`:
```php
#[Route('/dashboard', name: 'app_dashboard')]
public function dashboard(): Response
{
    return $this->render('dashboard.html.twig');
}
```

Then secure the route by adding to `security.yaml`:
```yaml
access_control:
    - { path: ^/dashboard, roles: ROLE_USER }
```

### Customize Registration Fields
Edit `src/Form/RegistrationFormType.php`:
- Add or remove fields
- Change validation rules
- Modify field labels

### Change Password Requirements
In `src/Form/RegistrationFormType.php`, modify:
```php
new Assert\Length([
    'min' => 8,  // Change minimum length
])
```

### Style Customization
Edit colors in `templates/base.html.twig`:
```css
--primary-color: #667eea;
--secondary-color: #764ba2;
```

---

## Useful Commands

### Check all routes
```bash
php bin/console debug:router
```

### Clear cache
```bash
php bin/console cache:clear
```

### Check security firewall
```bash
php bin/console debug:firewall
```

### Hash a password
```bash
php bin/console security:hash-password
```

### Check database connection
```bash
php bin/console doctrine:database:create
```

### Run SQL queries
```bash
php bin/console doctrine:query:sql "SHOW TABLES"
```

---

## To Do for Production

- [ ] Change `APP_ENV=dev` to `prod` in `.env`
- [ ] Set a strong `APP_SECRET` in `.env`
- [ ] Enable HTTPS
- [ ] Add rate limiting to login
- [ ] Add email verification for registration
- [ ] Add password reset functionality
- [ ] Set up proper error handling pages
- [ ] Add logging and monitoring

---

## Troubleshooting

### "Database connection failed"
Check your `.env` file DATABASE_URL is correct:
```
DATABASE_URL="mysql://root:@127.0.0.1:3306/mentis?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

### "User not found"
The user email must exist in the database. Test with:
```bash
php bin/console doctrine:query:sql "SELECT id, email FROM user LIMIT 5"
```

### "Invalid password"
Make sure the password is properly hashed using:
```bash
php bin/console security:hash-password
```

### "CSRF token is invalid"
Clear your cache:
```bash
php bin/console cache:clear
```

---

## Support

For more information:
- [Symfony Security Documentation](https://symfony.com/doc/current/security.html)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Symfony Form Documentation](https://symfony.com/doc/current/forms.html)
- [Twig Template Documentation](https://twig.symfony.com/)

---

**Ready to go! Visit http://localhost:8000 now** 🚀
