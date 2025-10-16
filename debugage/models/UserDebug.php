<?php

require_once 'User.php';
require_once 'GeneralDebug.php';

/**
 * Fonctions de debug pour la classe User
 */
class UserDebugger 
{
    /**
     * Affichage textuel de l'utilisateur
     */
    public static function userToString(User $user): string 
    {
        $status = $user->getId() !== null ? 'PERSISTÉ' : 'NOUVEAU';
        return sprintf(
            "User[%s] %s (%s) - créé le %s",
            $user->getId() ?? 'NULL',
            $user->getUsername(),
            $status,
            $user->getCreatedAt()->format('d/m/Y H:i')
        );
    }

    /**
     * Debug complet de l'utilisateur
     */
    public static function debugUser(User $user): array 
    {
        return [
            'user_state' => self::getUserState($user),
            'validation_status' => self::getValidationStatus($user),
            'security_analysis' => self::analyzeUserSecurity($user),
            'memory_usage' => memory_get_usage(true),
            'object_hash' => spl_object_hash($user)
        ];
    }

    /**
     * Analyse l'état de l'utilisateur
     */
    public static function getUserState(User $user): array 
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'is_new' => $user->getId() === null,
            'is_persisted' => $user->getId() !== null,
            'username_length' => strlen($user->getUsername()),
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'password_hash_algo' => self::detectPasswordAlgo($user->getPasswordHash())
        ];
    }

    /**
     * Détecte l'algorithme de hachage utilisé
     */
    private static function detectPasswordAlgo(string $hash): string 
    {
        if (str_starts_with($hash, '$argon2id$')) {
            return 'ARGON2ID (sécurisé)';
        } elseif (str_starts_with($hash, '$argon2i$')) {
            return 'ARGON2I (moins sécurisé)';
        } elseif (str_starts_with($hash, '$2y$')) {
            return 'BCRYPT (acceptable)';
        } else {
            return 'INCONNU (potentiellement dangereux)';
        }
    }

    /**
     * Valide les données de l'utilisateur
     */
    public static function getValidationStatus(User $user): array 
    {
        $username = $user->getUsername();
        
        return [
            'username_valid' => empty(User::validateUsername($username)),
            'username_errors' => User::validateUsername($username),
            'username_pattern_match' => preg_match('/^[a-zA-Z0-9_-]+$/', $username),
            'username_length_ok' => (strlen($username) >= 3 && strlen($username) <= 20),
            'has_password_hash' => !empty($user->getPasswordHash()),
            'password_algo_secure' => str_starts_with($user->getPasswordHash(), '$argon2id$')
        ];
    }

    /**
     * Analyse de sécurité de l'utilisateur
     */
    public static function analyzeUserSecurity(User $user): array 
    {
        $hash = $user->getPasswordHash();
        
        $security = [
            'hash_algorithm' => self::detectPasswordAlgo($hash),
            'hash_length' => strlen($hash),
            'username_security' => self::analyzeUsernameSecurity($user->getUsername()),
            'account_age_days' => self::getAccountAgeDays($user->getCreatedAt()),
            'potential_risks' => []
        ];

        // Détection des risques potentiels
        if (!str_starts_with($hash, '$argon2id$')) {
            $security['potential_risks'][] = 'Algorithme de hachage non optimal';
        }

        if (strlen($user->getUsername()) < 5) {
            $security['potential_risks'][] = 'Nom d\'utilisateur très court';
        }

        if (preg_match('/^(admin|test|user|guest)/i', $user->getUsername())) {
            $security['potential_risks'][] = 'Nom d\'utilisateur générique détecté';
        }

        return $security;
    }

    /**
     * Analyse la sécurité du nom d'utilisateur
     */
    private static function analyzeUsernameSecurity(string $username): array 
    {
        return [
            'length' => strlen($username),
            'has_numbers' => preg_match('/[0-9]/', $username),
            'has_letters' => preg_match('/[a-zA-Z]/', $username),
            'has_underscores' => str_contains($username, '_'),
            'has_dashes' => str_contains($username, '-'),
            'is_all_lowercase' => $username === strtolower($username),
            'is_all_uppercase' => $username === strtoupper($username),
            'entropy_score' => self::calculateUsernameEntropy($username)
        ];
    }

    /**
     * Calcule un score d'entropie simple pour le nom d'utilisateur
     */
    private static function calculateUsernameEntropy(string $username): float 
    {
        $uniqueChars = count(array_unique(str_split($username)));
        $totalChars = strlen($username);
        
        return $totalChars > 0 ? ($uniqueChars / $totalChars) * 100 : 0;
    }

    /**
     * Calcule l'âge du compte en jours
     */
    private static function getAccountAgeDays(DateTime $createdAt): int 
    {
        $now = new DateTime();
        $diff = $now->diff($createdAt);
        return $diff->days;
    }

    /**
     * Affiche l'état détaillé de l'utilisateur
     */
    public static function displayUserState(User $user): void 
    {
        echo "<h2>👤 État de l'utilisateur</h2>\n";
        echo "<div>" . self::userToString($user) . "</div>\n";
        
        $state = self::getUserState($user);
        echo "<h3>📊 Informations :</h3>\n";
        echo "<ul>\n";
        echo "<li>ID: " . ($state['id'] ?? 'NON ASSIGNÉ') . "</li>\n";
        echo "<li>Statut: " . ($state['is_persisted'] ? 'PERSISTÉ' : 'NOUVEAU') . "</li>\n";
        echo "<li>Nom d'utilisateur: {$state['username']} ({$state['username_length']} caractères)</li>\n";
        echo "<li>Algorithme mot de passe: {$state['password_hash_algo']}</li>\n";
        echo "<li>Créé le: {$state['created_at']}</li>\n";
        echo "</ul>\n";

        $validation = self::getValidationStatus($user);
        echo "<h3>✅ Validation :</h3>\n";
        echo "<ul>\n";
        echo "<li>Nom d'utilisateur valide: " . ($validation['username_valid'] ? '✅' : '❌') . "</li>\n";
        if (!empty($validation['username_errors'])) {
            echo "<li>Erreurs: " . implode(', ', $validation['username_errors']) . "</li>\n";
        }
        echo "<li>Mot de passe sécurisé: " . ($validation['password_algo_secure'] ? '✅' : '⚠️') . "</li>\n";
        echo "</ul>\n";

        $security = self::analyzeUserSecurity($user);
        if (!empty($security['potential_risks'])) {
            echo "<h3>⚠️ Risques potentiels :</h3>\n";
            echo "<ul>\n";
            foreach ($security['potential_risks'] as $risk) {
                echo "<li>$risk</li>\n";
            }
            echo "</ul>\n";
        }
    }

    /**
     * Test de validation avec différents mots de passe
     */
    public static function testPasswordValidation(): void 
    {
        echo "<h3>🔒 Test validation mots de passe</h3>\n";
        
        $testPasswords = [
            'password'           => 'Trop court, pas de chiffre, pas de spécial',
            'password123'        => 'Pas de caractère spécial',
            'password!'          => 'Pas de chiffre',
            'Pass123'            => 'Trop court',
            'MonMotDePasse123!'  => 'Valide',
            'Azerty123456789!'   => 'Valide',
            'SuperSecure2025@'   => 'Valide'
        ];

        foreach ($testPasswords as $password => $description) {
            $errors = User::validatePassword($password);
            $status = empty($errors) ? '✅ VALIDE' : '❌ INVALIDE';
            
            echo "<div><strong>$password</strong> → $status";
            if (!empty($errors)) {
                echo " (" . implode(', ', $errors) . ")";
            }
            echo "</div>\n";
        }
    }

    /**
     * Test de validation des noms d'utilisateur
     */
    public static function testUsernameValidation(): void 
    {
        echo "<h3>👥 Test validation noms d'utilisateur</h3>\n";
        
        $testUsernames = [
            'ab'                 => 'Trop court',
            'alice'              => 'Valide',
            'bob123'             => 'Valide',
            'user_name'          => 'Valide',
            'test-user'          => 'Valide',
            'user@domain'        => 'Caractère invalide (@)',
            'très_long_nom_utilisateur_ici' => 'Trop long',
            'Admin'              => 'Valide mais risqué',
            'user.name'          => 'Caractère invalide (.)'
        ];

        foreach ($testUsernames as $username => $description) {
            $errors = User::validateUsername($username);
            $status = empty($errors) ? '✅ VALIDE' : '❌ INVALIDE';
            
            echo "<div><strong>$username</strong> → $status";
            if (!empty($errors)) {
                echo " (" . implode(', ', $errors) . ")";
            }
            echo "</div>\n";
        }
    }

    /**
     * Simule le cycle de vie complet d'un utilisateur
     */
    public static function simulateUserLifecycle(): void 
    {
        echo "<h3>🔄 Simulation cycle de vie utilisateur</h3>\n";

        try {
            // Phase 1: Création
            echo "<h4>Phase 1: Création</h4>\n";
            $user = new User('alice_test', 'MonMotDePasse123!');
            echo "✅ Utilisateur créé: " . self::userToString($user) . "<br>\n";
            echo "État: " . ($user->getId() === null ? 'NOUVEAU' : 'PERSISTÉ') . "<br>\n";

            // Phase 2: Simulation persistance
            echo "<h4>Phase 2: Simulation persistance</h4>\n";
            $user->setId(42); // Simule l'assignation par la BDD
            echo "✅ ID assigné: " . self::userToString($user) . "<br>\n";
            echo "État: " . ($user->getId() !== null ? 'PERSISTÉ' : 'NOUVEAU') . "<br>\n";

            // Phase 3: Mise à jour
            echo "<h4>Phase 3: Mise à jour</h4>\n";
            $user->updateUsername('alice_updated');
            echo "✅ Nom d'utilisateur mis à jour: {$user->getUsername()}<br>\n";

            // Phase 4: Vérification mot de passe
            echo "<h4>Phase 4: Vérification mot de passe</h4>\n";
            $validPassword = $user->verifyPassword('MonMotDePasse123!');
            $invalidPassword = $user->verifyPassword('mauvais_password');
            echo "✅ Mot de passe correct: " . ($validPassword ? 'OUI' : 'NON') . "<br>\n";
            echo "❌ Mauvais mot de passe: " . ($invalidPassword ? 'OUI' : 'NON') . "<br>\n";

            // Phase 5: Sérialisation
            echo "<h4>Phase 5: Sérialisation</h4>\n";
            $userArray = $user->toArray();
            echo "✅ Sérialisé: " . json_encode($userArray, JSON_PRETTY_PRINT) . "<br>\n";

        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "<br>\n";
        }
    }

    /**
     * Benchmark des opérations utilisateur
     */
    public static function benchmarkUserOperations(): void 
    {
        echo "<h3>⏱️ Benchmark opérations utilisateur</h3>\n";

        $operations = [
            'creation' => function() {
                return new User('benchmark_user', 'TestPassword123!');
            },
            'password_verification' => function() {
                $user = new User('test', 'TestPassword123!');
                return $user->verifyPassword('TestPassword123!');
            },
            'username_validation' => function() {
                return User::validateUsername('test_user_123');
            },
            'password_validation' => function() {
                return User::validatePassword('TestPassword123!');
            },
            'serialization' => function() {
                $user = new User('test', 'TestPassword123!');
                return $user->toArray();
            }
        ];

        foreach ($operations as $name => $operation) {
            $result = DebugUtils::measureExecutionTime($operation);
            echo "<div><strong>$name:</strong> {$result['execution_time_ms']}ms (Mémoire: {$result['memory_used']})</div>\n";
        }
    }

    /**
     * Analyse de sécurité avancée
     */
    public static function advancedSecurityAnalysis(array $users): void 
    {
        echo "<h3>🔐 Analyse sécurité avancée</h3>\n";

        if (empty($users)) {
            echo "<div>Aucun utilisateur à analyser.</div>\n";
            return;
        }

        $stats = [
            'total_users' => count($users),
            'secure_passwords' => 0,
            'weak_usernames' => 0,
            'new_accounts' => 0,
            'algorithms' => []
        ];

        foreach ($users as $user) {
            if (!($user instanceof User)) continue;

            // Analyse mots de passe
            $hash = $user->getPasswordHash();
            if (str_starts_with($hash, '$argon2id$')) {
                $stats['secure_passwords']++;
            }

            // Analyse noms d'utilisateur
            if (strlen($user->getUsername()) < 5 || 
                preg_match('/^(admin|test|user|guest)/i', $user->getUsername())) {
                $stats['weak_usernames']++;
            }

            // Comptes nouveaux
            if ($user->getId() === null) {
                $stats['new_accounts']++;
            }

            // Algorithmes
            $algo = self::detectPasswordAlgo($hash);
            $stats['algorithms'][$algo] = ($stats['algorithms'][$algo] ?? 0) + 1;
        }

        echo "<h4>📈 Statistiques :</h4>\n";
        echo "<ul>\n";
        echo "<li>Total utilisateurs: {$stats['total_users']}</li>\n";
        echo "<li>Mots de passe sécurisés: {$stats['secure_passwords']}/" . $stats['total_users'] . "</li>\n";
        echo "<li>Noms d'utilisateur faibles: {$stats['weak_usernames']}</li>\n";
        echo "<li>Nouveaux comptes: {$stats['new_accounts']}</li>\n";
        echo "</ul>\n";

        echo "<h4>🔐 Répartition algorithmes :</h4>\n";
        echo "<ul>\n";
        foreach ($stats['algorithms'] as $algo => $count) {
            echo "<li>$algo: $count utilisateur(s)</li>\n";
        }
        echo "</ul>\n";
    }
}