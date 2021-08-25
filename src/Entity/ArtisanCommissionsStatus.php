<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArtisanCommissionsStatusRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ArtisanCommissionsStatusRepository::class)
 * @ORM\Table(name="artisans_commissions_statuses")
 *
 * NOTE: Ephemeral information, can be recreated by running update command. Table should not be committed, as that
 *       would generate too much noise in the repo history
 */
class ArtisanCommissionsStatus
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=Artisan::class, inversedBy="commissions")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Artisan $artisan = null;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private string $offer = '';

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isOpen = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArtisan(): ?Artisan
    {
        return $this->artisan;
    }

    public function setArtisan(?Artisan $artisan): self
    {
        $this->artisan = $artisan;

        return $this;
    }

    public function getOffer(): string
    {
        return $this->offer;
    }

    public function setOffer(string $offer): self
    {
        $this->offer = $offer;

        return $this;
    }

    public function getIsOpen(): bool
    {
        return $this->isOpen;
    }

    public function setIsOpen(bool $isOpen): self
    {
        $this->isOpen = $isOpen;

        return $this;
    }
}
