<?php

class UserController 
{
    private ?User $user;
    
    public function __construct() 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->user = $this->getCurrentUser();
    }
    
    
    public function handleRequest(): void 
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'profile';
        
        switch ($action) {
            case 'register':
                $this->handleRegistration();
                break;
                
            case 'login':
                $this->handleLogin();
                break;
                
            case 'logout':
                $this->handleLogout();
                break;
                
            case 'show_register':
                $this->showRegistrationForm();
                break;
                
            case 'show_login':
                $this->showLoginForm();
                break;
                
            case 'update_profile':
                $this->updateProfile();
                break;
                
            case 'change_password':
                $this->changePassword();
                break;
                
            case 'profile':
            default:
                $this->showProfile();
                break;
        }
    }
    
    private function showRegistrationForm(): void 
    {
        if ($this->user) {
            $this->redirect('?controller=user&action=profile');
            return;
        }
        
        $data = [
            'page_title' => 'Memory Game - Inscription'
        ];
        
        $this->render('register', $data);
    }
    
   
    private function showLoginForm(): void 
    {
        if ($this->user) {
            $this->redirect('?controller=user&action=profile');
            return;
        }
        
        $data = [
            'page_title' => 'Memory Game - Connexion'
        ];
        
        $this->render('login', $data);
    }
    
   
    private function handleRegistration(): void 
    {
        try {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $usernameErrors = User::validateUsername($username);
            $passwordErrors = User::validatePassword($password);
            
            $errors = array_merge($usernameErrors, $passwordErrors);
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Les mots de passe ne correspondent pas';
            }
            
            
            if (User::usernameExists($username)) {
                $errors[] = 'Ce nom d\'utilisateur est déjà pris';
            }
            
            if (!empty($errors)) {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => implode('. ', $errors)
                ];
                $_SESSION['form_data'] = ['username' => $username]; 
                $this->redirect('?controller=user&action=show_register');
                return;
            }
            
            $userId = User::create($username, $password);
            
            if ($userId) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => "Bienvenue {$username} ! Votre compte a été créé avec succès."
                ];
                
                $this->redirect('?controller=game&action=menu');
            } else {
                throw new Exception('Erreur lors de la création du compte');
            }
            
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Une erreur est survenue lors de l\'inscription'
            ];
            $this->redirect('?controller=user&action=show_register');
        }
    }
    
    
    private function handleLogin(): void 
    {
        try {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Nom d\'utilisateur et mot de passe requis'
                ];
                $_SESSION['form_data'] = ['username' => $username];
                $this->redirect('?controller=user&action=show_login');
                return;
            }
            
            $user = User::authenticate($username, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user->getId();
                $_SESSION['username'] = $user->getUsername();
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => "Bon retour {$username} !"
                ];
                
                $this->redirect('?controller=game&action=menu');
            } else {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Nom d\'utilisateur ou mot de passe incorrect'
                ];
                $_SESSION['form_data'] = ['username' => $username];
                $this->redirect('?controller=user&action=show_login');
            }
            
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Une erreur est survenue lors de la connexion'
            ];
            $this->redirect('?controller=user&action=show_login');
        }
    }
    
    
    private function handleLogout(): void 
    {
        $username = $_SESSION['username'] ?? 'Utilisateur';
        
        session_destroy();
        session_start();
        
        $_SESSION['message'] = [
            'type' => 'info',
            'text' => "Au revoir {$username} ! Vous êtes déconnecté."
        ];
        
        $this->redirect('?controller=game&action=menu');
    }
    
   
    private function showProfile(): void 
    {
        if (!$this->user) {
            $_SESSION['message'] = [
                'type' => 'warning',
                'text' => 'Connectez-vous pour accéder à votre profil'
            ];
            $this->redirect('?controller=user&action=show_login');
            return;
        }
        
        try {
            
            $stats = $this->user->getStatistics();
            
           
            $personalScores = Score::getPlayerScores($this->user->getId(), 10);
            
            $data = [
                'page_title' => 'Memory Game - Mon Profil',
                'user' => $this->user,
                'stats' => $stats,
                'recent_scores' => $personalScores
            ];
            
            $this->render('profile', $data);
            
        } catch (Exception $e) {
            error_log('Profile error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors du chargement du profil'
            ];
            $this->redirect('?controller=game&action=menu');
        }
    }
    
    
    private function updateProfile(): void 
    {
        if (!$this->user) {
            $this->redirect('?controller=user&action=show_login');
            return;
        }
        
        try {
            $newUsername = trim($_POST['username'] ?? '');
            
            if (empty($newUsername)) {
                throw new InvalidArgumentException('Le nom d\'utilisateur est requis');
            }
            
            
            $errors = User::validateUsername($newUsername);
            
            
            if ($newUsername !== $this->user->getUsername() && User::usernameExists($newUsername)) {
                $errors[] = 'Ce nom d\'utilisateur est déjà pris';
            }
            
            if (!empty($errors)) {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => implode('. ', $errors)
                ];
                $this->redirect('?controller=user&action=profile');
                return;
            }
            
            $this->user->updateUsername($newUsername);
            
            $this->updateUserInDatabase($this->user);
            
            $_SESSION['username'] = $newUsername;
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Profil mis à jour avec succès'
            ];
            
            $this->redirect('?controller=user&action=profile');
            
        } catch (Exception $e) {
            error_log('Update profile error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ];
            $this->redirect('?controller=user&action=profile');
        }
    }
    
   
    private function changePassword(): void 
    {
        if (!$this->user) {
            $this->redirect('?controller=user&action=show_login');
            return;
        }
        
        try {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            
            if (!$this->user->verifyPassword($currentPassword)) {
                throw new InvalidArgumentException('Mot de passe actuel incorrect');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new InvalidArgumentException('Les nouveaux mots de passe ne correspondent pas');
            }
            
            
            $errors = User::validatePassword($newPassword);
            
            if (!empty($errors)) {
                throw new InvalidArgumentException(implode('. ', $errors));
            }
            
            
            $this->user->updatePassword($newPassword);
            
            $this->updateUserInDatabase($this->user);
            
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Mot de passe changé avec succès'
            ];
            
            $this->redirect('?controller=user&action=profile');
            
        } catch (Exception $e) {
            error_log('Change password error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => $e->getMessage()
            ];
            $this->redirect('?controller=user&action=profile');
        }
    }
    
   
    private function updateUserInDatabase(User $user): void 
    {
        $pdo = Database::getInstance();
        $sql = "UPDATE players SET username = ?, password_hash = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user->getUsername(),
            $user->getPasswordHash(),
            $user->getId()
        ]);
    }
    
    
    private function getCurrentUser(): ?User 
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        try {
            return User::findById($_SESSION['user_id']);
        } catch (Exception $e) {
            error_log('Current user error: ' . $e->getMessage());
            return null;
        }
    }
    
    
    private function render(string $view, array $data = []): void 
    {
        $flash_message = $this->getFlashMessage();
        $page_title = $data['page_title'] ?? 'Memory Game';
        $user = $data['user'] ?? null;
        $stats = $data['stats'] ?? [];
        $recent_scores = $data['recent_scores'] ?? [];
        $form_data = $this->getFormData(); // Pour conserver les saisies en cas d'erreur
        
        require_once "views/user/{$view}.php";
    }
    
   
    private function redirect(string $url): void 
    {
        header("Location: {$url}");
        exit;
    }
    
    
    private function getFlashMessage(): ?array 
    {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
            return $message;
        }
        return null;
    }
    
   
    private function getFormData(): array 
    {
        if (isset($_SESSION['form_data'])) {
            $data = $_SESSION['form_data'];
            unset($_SESSION['form_data']);
            return $data;
        }
        return [];
    }
}


class ValidationException extends Exception {}
class SecurityException extends Exception {}