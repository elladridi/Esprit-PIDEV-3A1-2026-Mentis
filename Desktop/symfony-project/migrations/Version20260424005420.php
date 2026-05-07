<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424005420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE goal ADD title VARCHAR(255) NOT NULL, ADD is_completed TINYINT DEFAULT 0 NOT NULL, ADD created_at DATETIME NOT NULL, DROP progress, DROP status, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE deadline deadline DATETIME NOT NULL');
        $this->addSql('ALTER TABLE goal ADD CONSTRAINT FK_FCDCEB2EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('DROP INDEX idx_user ON goal');
        $this->addSql('CREATE INDEX IDX_FCDCEB2EA76ED395 ON goal (user_id)');
        $this->addSql('ALTER TABLE login_attempts CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE was_successful was_successful TINYINT NOT NULL, CHANGE user_agent user_agent LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_date ON mood');
        $this->addSql('ALTER TABLE mood ADD created_at DATETIME NOT NULL, DROP date, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE feeling feeling VARCHAR(255) NOT NULL, CHANGE note note LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT FK_339AEF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('DROP INDEX idx_user ON mood');
        $this->addSql('CREATE INDEX IDX_339AEF6A76ED395 ON mood (user_id)');
        $this->addSql('ALTER TABLE sessions CHANGE average_rating average_rating DOUBLE PRECISION DEFAULT 0');
        $this->addSql('DROP INDEX idx_email ON user');
        $this->addSql('DROP INDEX idx_type ON user');
        $this->addSql('ALTER TABLE user CHANGE face_enabled face_enabled TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE goal DROP FOREIGN KEY FK_FCDCEB2EA76ED395');
        $this->addSql('ALTER TABLE goal DROP FOREIGN KEY FK_FCDCEB2EA76ED395');
        $this->addSql('ALTER TABLE goal ADD progress INT DEFAULT NULL, ADD status VARCHAR(50) DEFAULT NULL, DROP title, DROP is_completed, DROP created_at, CHANGE id id INT NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE deadline deadline DATE DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_fcdceb2ea76ed395 ON goal');
        $this->addSql('CREATE INDEX idx_user ON goal (user_id)');
        $this->addSql('ALTER TABLE goal ADD CONSTRAINT FK_FCDCEB2EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE login_attempts CHANGE id id INT NOT NULL, CHANGE was_successful was_successful TINYINT DEFAULT 0 NOT NULL, CHANGE user_agent user_agent TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY FK_339AEF6A76ED395');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY FK_339AEF6A76ED395');
        $this->addSql('ALTER TABLE mood ADD date DATETIME DEFAULT NULL, DROP created_at, CHANGE id id INT NOT NULL, CHANGE feeling feeling VARCHAR(50) DEFAULT NULL, CHANGE note note TEXT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_date ON mood (date)');
        $this->addSql('DROP INDEX idx_339aef6a76ed395 ON mood');
        $this->addSql('CREATE INDEX idx_user ON mood (user_id)');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT FK_339AEF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE sessions CHANGE average_rating average_rating DOUBLE PRECISION DEFAULT \'0\'');
        $this->addSql('ALTER TABLE `user` CHANGE face_enabled face_enabled TINYINT NOT NULL');
        $this->addSql('CREATE INDEX idx_email ON `user` (email)');
        $this->addSql('CREATE INDEX idx_type ON `user` (type)');
    }
}
