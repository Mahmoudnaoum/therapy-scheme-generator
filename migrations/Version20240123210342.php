<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240123210342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE label_stub DROP FOREIGN KEY FK_36FA08E9E942160A');
        $this->addSql('ALTER TABLE label_stub DROP FOREIGN KEY FK_36FA08E933B92F39');
        $this->addSql('ALTER TABLE label_stub ADD id INT AUTO_INCREMENT NOT NULL, ADD position INT DEFAULT 0 NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE label_stub ADD CONSTRAINT FK_36FA08E9E942160A FOREIGN KEY (stub_id) REFERENCES stub (id)');
        $this->addSql('ALTER TABLE label_stub ADD CONSTRAINT FK_36FA08E933B92F39 FOREIGN KEY (label_id) REFERENCES label (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE label_stub MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE label_stub DROP FOREIGN KEY FK_36FA08E933B92F39');
        $this->addSql('ALTER TABLE label_stub DROP FOREIGN KEY FK_36FA08E9E942160A');
        $this->addSql('DROP INDEX `PRIMARY` ON label_stub');
        $this->addSql('ALTER TABLE label_stub DROP id, DROP position');
        $this->addSql('ALTER TABLE label_stub ADD CONSTRAINT FK_36FA08E933B92F39 FOREIGN KEY (label_id) REFERENCES label (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE label_stub ADD CONSTRAINT FK_36FA08E9E942160A FOREIGN KEY (stub_id) REFERENCES stub (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE label_stub ADD PRIMARY KEY (label_id, stub_id)');
    }
}
