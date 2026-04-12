<?php

namespace App\Entity;

use App\Repository\AssessmentResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssessmentResultRepository::class)]
#[ORM\Table(name: 'assessmentresult')]  // Table name is 'assessmentresult' without underscore
class AssessmentResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'result_id', type: 'integer')]
    private ?int $resultId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Assessment::class)]
    #[ORM\JoinColumn(name: 'assessment_id', referencedColumnName: 'assessment_id')]
    private ?Assessment $assessment = null;

    #[ORM\Column(name: 'total_score', type: 'integer', nullable: true)]
    private ?int $totalScore = null;

    #[ORM\Column(name: 'risk_level', type: 'string', length: 20, nullable: true)]
    private ?string $riskLevel = null;

    #[ORM\Column(name: 'interpretation', type: 'text', nullable: true)]
    private ?string $interpretation = null;

    #[ORM\Column(name: 'recommended_content', type: 'text', nullable: true)]
    private ?string $recommendedContent = null;

    #[ORM\Column(name: 'suggest_session', type: 'boolean', nullable: true)]
    private ?bool $suggestSession = false;

    #[ORM\Column(name: 'taken_at', type: 'date', nullable: true)]
    private ?\DateTimeInterface $takenAt = null;

    // ============= GETTERS & SETTERS =============
    
    public function getResultId(): ?int
    {
        return $this->resultId;
    }

    public function setResultId(int $resultId): self
    {
        $this->resultId = $resultId;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAssessment(): ?Assessment
    {
        return $this->assessment;
    }

    public function setAssessment(?Assessment $assessment): self
    {
        $this->assessment = $assessment;
        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function setTotalScore(?int $totalScore): self
    {
        $this->totalScore = $totalScore;
        return $this;
    }

    public function getRiskLevel(): ?string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(?string $riskLevel): self
    {
        $this->riskLevel = $riskLevel;
        return $this;
    }

    public function getInterpretation(): ?string
    {
        return $this->interpretation;
    }

    public function setInterpretation(?string $interpretation): self
    {
        $this->interpretation = $interpretation;
        return $this;
    }

    public function getRecommendedContent(): ?string
    {
        return $this->recommendedContent;
    }

    public function setRecommendedContent(?string $recommendedContent): self
    {
        $this->recommendedContent = $recommendedContent;
        return $this;
    }

    public function isSuggestSession(): ?bool
    {
        return $this->suggestSession;
    }

    public function setSuggestSession(?bool $suggestSession): self
    {
        $this->suggestSession = $suggestSession;
        return $this;
    }

    public function getTakenAt(): ?\DateTimeInterface
    {
        return $this->takenAt;
    }

    public function setTakenAt(?\DateTimeInterface $takenAt): self
    {
        $this->takenAt = $takenAt;
        return $this;
    }
}