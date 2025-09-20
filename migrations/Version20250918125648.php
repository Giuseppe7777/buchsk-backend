<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250918125648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_otp (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, code VARCHAR(6) NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_used TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4C8AF94AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_otp ADD CONSTRAINT FK_4C8AF94AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_otp DROP FOREIGN KEY FK_4C8AF94AA76ED395');
        $this->addSql('DROP TABLE user_otp');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
