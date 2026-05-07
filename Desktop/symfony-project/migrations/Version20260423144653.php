<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423144653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_registrations (id INT AUTO_INCREMENT NOT NULL, user_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, ticket_type VARCHAR(50) DEFAULT \'STANDARD\' NOT NULL, number_of_tickets INT DEFAULT 1 NOT NULL, total_price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, status VARCHAR(50) DEFAULT \'CONFIRMED\' NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, special_requests LONGTEXT DEFAULT NULL, registration_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, qr_code_path VARCHAR(255) DEFAULT NULL, confirmation_number VARCHAR(100) DEFAULT NULL, event_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_7787E14B71F7E88B (event_id), INDEX IDX_7787E14BA76ED395 (user_id), UNIQUE INDEX unique_email_event (email, event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT FK_7787E14B71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT FK_7787E14BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('DROP TABLE pending_reminders');
        $this->addSql('DROP TABLE user_old');
        $this->addSql('DROP INDEX idx_type ON assessment');
        $this->addSql('DROP INDEX idx_status ON assessment');
        $this->addSql('ALTER TABLE assessment CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_event_type ON events');
        $this->addSql('DROP INDEX idx_date_time ON events');
        $this->addSql('DROP INDEX idx_status ON events');
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
        $this->addSql('CREATE TABLE pending_reminders (reminder_id INT NOT NULL, session_id INT NOT NULL, patient_id INT NOT NULL, weather_forecast TEXT CHARACTER SET latin1 DEFAULT NULL COLLATE `latin1_swedish_ci`, shown TINYINT DEFAULT 0, created_at DATETIME DEFAULT NULL, INDEX session_id (session_id), INDEX patient_id (patient_id), PRIMARY KEY (reminder_id)) DEFAULT CHARACTER SET latin1 COLLATE `latin1_swedish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_old (id INT NOT NULL, firstname VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, lastname VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, dateofbirth VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(1000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY FK_7787E14B71F7E88B');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY FK_7787E14BA76ED395');
        $this->addSql('DROP TABLE event_registrations');
        $this->addSql('ALTER TABLE assessment CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_type ON assessment (type)');
        $this->addSql('CREATE INDEX idx_status ON assessment (status)');
        $this->addSql('CREATE INDEX idx_event_type ON events (event_type)');
        $this->addSql('CREATE INDEX idx_date_time ON events (date_time)');
        $this->addSql('CREATE INDEX idx_status ON events (status)');
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
