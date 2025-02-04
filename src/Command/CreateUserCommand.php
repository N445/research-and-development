<?php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Créer un utilisateur avec email, mot de passe et rôle')]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        // Demander l'email
        $question = new Question('Email de l\'utilisateur: ');
        $question->setValidator(function ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Adresse email invalide.');
            }
            return $email;
        });
        $question->setMaxAttempts(3);
        $email = $helper->ask($input, $output, $question);

        // Demander le mot de passe
        $question = new Question('Mot de passe: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $question->setValidator(function ($password) {
            if (strlen($password) < 6) {
                throw new \RuntimeException('Le mot de passe doit faire au moins 6 caractères.');
            }
            return $password;
        });
        $question->setMaxAttempts(3);
        $password = $helper->ask($input, $output, $question);

        // Demander le rôle
        $question = new ChoiceQuestion('Choisissez un rôle: ', ['ROLE_USER', 'ROLE_ADMIN'], 0);
        $role = $helper->ask($input, $output, $question);

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles([$role]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Utilisateur créé avec succès!</info>');
        return Command::SUCCESS;
    }
}
