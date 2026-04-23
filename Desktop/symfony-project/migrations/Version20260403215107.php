<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403215107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assessmentresult DROP FOREIGN KEY `assessmentresult_ibfk_1`');
        $this->addSql('ALTER TABLE assessmentresult DROP FOREIGN KEY `assessmentresult_ibfk_2`');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY `events_ibfk_1`');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY `event_registrations_ibfk_1`');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY `event_registrations_ibfk_2`');
        $this->addSql('ALTER TABLE goal DROP FOREIGN KEY `goal_ibfk_1`');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY `mood_ibfk_1`');
        $this->addSql('ALTER TABLE pending_reminders DROP FOREIGN KEY `fk_pending_reminders_patient`');
        $this->addSql('ALTER TABLE pending_reminders DROP FOREIGN KEY `fk_pending_reminders_session`');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY `question_ibfk_1`');
        $this->addSql('ALTER TABLE sessions DROP FOREIGN KEY `sessions_ibfk_1`');
        $this->addSql('ALTER TABLE session_review DROP FOREIGN KEY `session_review_ibfk_1`');
        $this->addSql('ALTER TABLE session_review DROP FOREIGN KEY `session_review_ibfk_2`');
        $this->addSql('DROP TABLE assessment');
        $this->addSql('DROP TABLE assessmentresult');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE event_registrations');
        $this->addSql('DROP TABLE goal');
        $this->addSql('DROP TABLE mood');
        $this->addSql('DROP TABLE pending_reminders');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE sessions');
        $this->addSql('DROP TABLE session_review');
        $this->addSql('DROP TABLE user_old');
        $this->addSql('ALTER TABLE content_node DROP FOREIGN KEY `content_node_ibfk_1`');
        $this->addSql('ALTER TABLE content_node DROP FOREIGN KEY `content_node_ibfk_2`');
        $this->addSql('ALTER TABLE content_node CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE assigned_users assigned_users LONGTEXT NOT NULL');
        $this->addSql('DROP INDEX idx_created_by ON content_node');
        $this->addSql('CREATE INDEX IDX_481D0580DE12AB56 ON content_node (created_by)');
        $this->addSql('DROP INDEX idx_parent ON content_node');
        $this->addSql('CREATE INDEX IDX_481D05803445EB91 ON content_node (parent_node_id)');
        $this->addSql('ALTER TABLE content_node ADD CONSTRAINT `content_node_ibfk_1` FOREIGN KEY (created_by) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_node ADD CONSTRAINT `content_node_ibfk_2` FOREIGN KEY (parent_node_id) REFERENCES content_node (node_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE content_path DROP FOREIGN KEY `content_path_ibfk_1`');
        $this->addSql('ALTER TABLE content_path DROP FOREIGN KEY `content_path_ibfk_2`');
        $this->addSql('ALTER TABLE content_path CHANGE accessed_at accessed_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_user ON content_path');
        $this->addSql('CREATE INDEX IDX_C63666CAA76ED395 ON content_path (user_id)');
        $this->addSql('DROP INDEX idx_node ON content_path');
        $this->addSql('CREATE INDEX IDX_C63666CA460D9FD7 ON content_path (node_id)');
        $this->addSql('ALTER TABLE content_path ADD CONSTRAINT `content_path_ibfk_1` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_path ADD CONSTRAINT `content_path_ibfk_2` FOREIGN KEY (node_id) REFERENCES content_node (node_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user DROP INDEX idx_email, ADD UNIQUE INDEX UNIQ_8D93D649E7927C74 (email)');
        $this->addSql('DROP INDEX idx_type ON user');
        $this->addSql('ALTER TABLE user ADD gender VARCHAR(10) DEFAULT NULL, CHANGE face_data face_data LONGTEXT DEFAULT NULL, CHANGE face_enabled face_enabled TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assessment (assessment_id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, type VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, status VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'active\' COLLATE `utf8mb4_general_ci`, created_at DATE DEFAULT CURRENT_DATE, image_path VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX idx_status (status), INDEX idx_type (type), PRIMARY KEY (assessment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE assessmentresult (result_id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, assessment_id INT DEFAULT NULL, total_score INT DEFAULT NULL, risk_level VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, interpretation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, recommended_content TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, suggest_session TINYINT DEFAULT NULL, taken_at DATE DEFAULT CURRENT_DATE, INDEX idx_assessment (assessment_id), INDEX idx_user (user_id), PRIMARY KEY (result_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_time DATETIME NOT NULL, location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, max_participants INT NOT NULL, current_participants INT DEFAULT 0, event_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, price NUMERIC(10, 2) DEFAULT \'0.00\', image_url VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'UPCOMING\' COLLATE `utf8mb4_general_ci`, created_by INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_date_time (date_time), INDEX idx_status (status), INDEX created_by (created_by), INDEX idx_event_type (event_type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE event_registrations (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, user_id INT DEFAULT NULL, user_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ticket_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'STANDARD\' COLLATE `utf8mb4_general_ci`, number_of_tickets INT DEFAULT 1, total_price NUMERIC(10, 2) DEFAULT \'0.00\', status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'CONFIRMED\' COLLATE `utf8mb4_general_ci`, payment_method VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, special_requests TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, registration_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_user_id (user_id), UNIQUE INDEX unique_email_event (email, event_id), INDEX idx_status (status), INDEX idx_registration_date (registration_date), INDEX idx_event_id (event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE goal (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, deadline DATE DEFAULT NULL, progress INT DEFAULT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX idx_user (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE mood (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, feeling VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, note TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date DATETIME DEFAULT NULL, INDEX idx_user (user_id), INDEX idx_date (date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE pending_reminders (reminder_id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, patient_id INT NOT NULL, weather_forecast TEXT CHARACTER SET latin1 DEFAULT NULL COLLATE `latin1_swedish_ci`, shown TINYINT DEFAULT 0, created_at DATETIME DEFAULT NULL, INDEX patient_id (patient_id), INDEX session_id (session_id), PRIMARY KEY (reminder_id)) DEFAULT CHARACTER SET latin1 COLLATE `latin1_swedish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE question (question_id INT AUTO_INCREMENT NOT NULL, assessment_id INT DEFAULT NULL, text TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, scale VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX idx_assessment (assessment_id), PRIMARY KEY (question_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sessions (session_id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, session_date DATE NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, location VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, session_type VARCHAR(100) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, status VARCHAR(20) CHARACTER SET latin1 DEFAULT \'Scheduled\' COLLATE `latin1_swedish_ci`, reserved_by INT DEFAULT NULL, reserved_at DATETIME DEFAULT NULL, category VARCHAR(100) CHARACTER SET latin1 DEFAULT \'General\' COLLATE `latin1_swedish_ci`, popularity INT DEFAULT 0, average_rating DOUBLE PRECISION DEFAULT \'0\', meeting_link VARCHAR(500) CHARACTER SET latin1 DEFAULT NULL COLLATE `latin1_swedish_ci`, meeting_started TINYINT DEFAULT 0, meeting_ended TINYINT DEFAULT 0, reminder_sent TINYINT DEFAULT 0, patient_confirmed TINYINT DEFAULT 0, confirmed_at DATETIME DEFAULT NULL, max_participants INT DEFAULT 20, current_participants INT DEFAULT 0, price NUMERIC(10, 2) DEFAULT \'0.00\', INDEX idx_session_type (session_type), INDEX idx_status (status), INDEX reserved_by (reserved_by), INDEX idx_category (category), INDEX idx_session_date (session_date), PRIMARY KEY (session_id)) DEFAULT CHARACTER SET latin1 COLLATE `latin1_swedish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE session_review (review_id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, patient_id INT NOT NULL, rating INT NOT NULL, comment TEXT CHARACTER SET latin1 DEFAULT NULL COLLATE `latin1_swedish_ci`, review_date DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_session (session_id), INDEX idx_patient (patient_id), PRIMARY KEY (review_id)) DEFAULT CHARACTER SET latin1 COLLATE `latin1_swedish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_old (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, lastname VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, dateofbirth VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(1000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE assessmentresult ADD CONSTRAINT `assessmentresult_ibfk_1` FOREIGN KEY (assessment_id) REFERENCES assessment (assessment_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE assessmentresult ADD CONSTRAINT `assessmentresult_ibfk_2` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (created_by) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE goal ADD CONSTRAINT `goal_ibfk_1` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT `mood_ibfk_1` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE pending_reminders ADD CONSTRAINT `fk_pending_reminders_patient` FOREIGN KEY (patient_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pending_reminders ADD CONSTRAINT `fk_pending_reminders_session` FOREIGN KEY (session_id) REFERENCES sessions (session_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT `question_ibfk_1` FOREIGN KEY (assessment_id) REFERENCES assessment (assessment_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sessions ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (reserved_by) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE session_review ADD CONSTRAINT `session_review_ibfk_1` FOREIGN KEY (session_id) REFERENCES sessions (session_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session_review ADD CONSTRAINT `session_review_ibfk_2` FOREIGN KEY (patient_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_node DROP FOREIGN KEY FK_481D0580DE12AB56');
        $this->addSql('ALTER TABLE content_node DROP FOREIGN KEY FK_481D05803445EB91');
        $this->addSql('ALTER TABLE content_node CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE assigned_users assigned_users TEXT DEFAULT \'[]\'');
        $this->addSql('DROP INDEX idx_481d05803445eb91 ON content_node');
        $this->addSql('CREATE INDEX idx_parent ON content_node (parent_node_id)');
        $this->addSql('DROP INDEX idx_481d0580de12ab56 ON content_node');
        $this->addSql('CREATE INDEX idx_created_by ON content_node (created_by)');
        $this->addSql('ALTER TABLE content_node ADD CONSTRAINT FK_481D0580DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_node ADD CONSTRAINT FK_481D05803445EB91 FOREIGN KEY (parent_node_id) REFERENCES content_node (node_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE content_path DROP FOREIGN KEY FK_C63666CAA76ED395');
        $this->addSql('ALTER TABLE content_path DROP FOREIGN KEY FK_C63666CA460D9FD7');
        $this->addSql('ALTER TABLE content_path CHANGE accessed_at accessed_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('DROP INDEX idx_c63666ca460d9fd7 ON content_path');
        $this->addSql('CREATE INDEX idx_node ON content_path (node_id)');
        $this->addSql('DROP INDEX idx_c63666caa76ed395 ON content_path');
        $this->addSql('CREATE INDEX idx_user ON content_path (user_id)');
        $this->addSql('ALTER TABLE content_path ADD CONSTRAINT FK_C63666CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE content_path ADD CONSTRAINT FK_C63666CA460D9FD7 FOREIGN KEY (node_id) REFERENCES content_node (node_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user` DROP INDEX UNIQ_8D93D649E7927C74, ADD INDEX idx_email (email)');
        $this->addSql('ALTER TABLE `user` DROP gender, CHANGE face_data face_data TEXT DEFAULT NULL, CHANGE face_enabled face_enabled TINYINT DEFAULT 0');
        $this->addSql('CREATE INDEX idx_type ON `user` (type)');
    }
}
