<?php

class User {
    private ?int $id = null;
    private string $username;
    private string $passwordHash;
    private DateTime $createdAt;
    
    public function __construct(string $username, string $password = '') 
    {
        $this->setUsername($username);
        if ($password) {
            $this->setPassword($password);
        }
        $this->createdAt = new DateTime();
    }
    
    public static function create(string $username, string $password): ?int {
    try {
        $user = new self($username, $password);
        
        $pdo = Database::getInstance();
        $sql = "INSERT INTO players (username, password_hash) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        
        $result = $stmt->execute([$user->username, $user->passwordHash]);
        
        return $result ? $pdo->lastInsertId() : null;
        
    } catch (Exception $e) {
        error_log('User creation error: ' . $e->getMessage());
        return null;
    }
}


    public static function authenticate(string $username, string $password): ?self 
{
    try {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM players WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        
        $userData = $stmt->fetch();
        
        if ($userData && password_verify($password, $userData['password_hash'])) {
            $user = new self($userData['username']);
            $user->setId($userData['id']);
            $user->passwordHash = $userData['password_hash'];
            $user->setCreatedAt(new DateTime($userData['created_at']));
            
            return $user;
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log('Authentication error: ' . $e->getMessage());
        return null;
    }
}


    public static function findById(int $id): ?self {
    try {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM players WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $userData = $stmt->fetch();
        
        if ($userData) {
            $user = new self($userData['username']);
            $user->setId($userData['id']);
            $user->passwordHash = $userData['password_hash'];
            $user->setCreatedAt(new DateTime($userData['created_at']));
            
            return $user;
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log('Find user error: ' . $e->getMessage());
        return null;
    }
}


    public static function usernameExists(string $username): bool {
    try {
        $pdo = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM players WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        
        return $stmt->fetchColumn() > 0;
        
    } catch (Exception $e) {
        error_log('Username check error: ' . $e->getMessage());
        return false;
    }
}


   public function getStatistics(): array {
    try {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM player_stats WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->id]);
        
        $stats = $stmt->fetch();
        
        return $stats ?: [
            'total_games' => 0,
            'avg_moves' => 0,
            'best_time' => 0,
            'personal_best' => 0,
            'member_since' => $this->createdAt->format('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        error_log('User stats error: ' . $e->getMessage());
        return [];
    }
}

    
    private function setUsername(string $username): void {
        $username = trim($username);
        if (strlen($username) < 3 || strlen($username) > 20) {
            throw new InvalidArgumentException("Le nom d'utilisateur doit faire entre 3 et 20 caractères");
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            throw new InvalidArgumentException("Le nom d'utilisateur ne peut contenir que des lettres, chiffres, _ et -");
        }
        $this->username = $username;
    }
    
    private function setPassword(string $password): void {
        
        if (strlen($password) < 12) {
            throw new InvalidArgumentException("Le mot de passe doit faire au moins 12 caractères");
        }
        
      
        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins un chiffre");
        }
        
        
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins un caractère spécial");
        }
        
        $this->passwordHash = password_hash($password, PASSWORD_ARGON2ID);
    }
    
    
    // Getters

    
    
    public function getId(): ?int 
    {
        return $this->id;
    }
    
    public function getUsername(): string 
    {
        return $this->username;
    }
    
    public function getPasswordHash(): string 
    {
        return $this->passwordHash;
    }
    
    public function getCreatedAt(): DateTime 
    {
        return clone $this->createdAt;
    }
    
    // Setters
    
    public function setId(int $id): void {
        if ($this->id !== null) {
            throw new LogicException("L'ID ne peut être défini qu'une seule fois");
        }
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }
        $this->id = $id;
    }
    
    public function updateUsername(string $username): void {
        $this->setUsername($username);
    }
    
    public function updatePassword(string $password): void 
    {
        $this->setPassword($password);
    }
    
    public function setCreatedAt(DateTime $createdAt): void 
    {
        $this->createdAt = clone $createdAt;
    }
    
    
    
    public function verifyPassword(string $password): bool 
    {
        return password_verify($password, $this->passwordHash);
    }
    
    
   
    
    public static function validatePassword(string $password): array 
    {
        $errors = [];
        
        if (strlen($password) < 12) {
            $errors[] = "Le mot de passe doit faire au moins 12 caractères";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
        }
        
        return $errors;
    }
    
    public static function validateUsername(string $username): array 
    {
        $errors = [];
        $username = trim($username);
        
        if (strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = "Le nom d'utilisateur doit faire entre 3 et 20 caractères";
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres, _ et -";
        }
        
        return $errors;
    }
    
    
    public function toArray(): array 
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }
    
    public function toArrayComplete(): array 
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'password_hash' => $this->passwordHash,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }
    
    public static function fromArray(array $data): self 
    {
        if (!isset($data['username'])) {
            throw new InvalidArgumentException("Le nom d'utilisateur est requis");
        }
        
        $user = new self($data['username']);
        
        if (isset($data['id'])) {
            $user->setId((int)$data['id']);
        }
        
        if (isset($data['password_hash'])) {
            $user->passwordHash = $data['password_hash'];
        }
        
        if (isset($data['created_at'])) {
            $user->setCreatedAt(new DateTime($data['created_at']));
        }
        
        return $user;
    }
    

}