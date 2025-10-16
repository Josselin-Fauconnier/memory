<?php

class User 
{
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
    
    
    private function setUsername(string $username): void 
    {
        $username = trim($username);
        if (strlen($username) < 3 || strlen($username) > 20) {
            throw new InvalidArgumentException("Le nom d'utilisateur doit faire entre 3 et 20 caractères");
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            throw new InvalidArgumentException("Le nom d'utilisateur ne peut contenir que des lettres, chiffres, _ et -");
        }
        $this->username = $username;
    }
    
    private function setPassword(string $password): void 
    {
        
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
    
    public function setId(int $id): void 
    {
        if ($this->id !== null) {
            throw new LogicException("L'ID ne peut être défini qu'une seule fois");
        }
        if ($id <= 0) {
            throw new InvalidArgumentException("L'ID doit être un entier positif");
        }
        $this->id = $id;
    }
    
    public function updateUsername(string $username): void 
    {
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