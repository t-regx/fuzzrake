<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArtisanUrlRepository;
use App\Tracking\Web\Url\Fetchable;
use App\Utils\DateTime\UtcClock;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Entity(repositoryClass: ArtisanUrlRepository::class)]
#[ORM\Table(name: 'artisans_urls')]
class ArtisanUrl implements Fetchable, Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Artisan::class, inversedBy: 'urls')]
    #[ORM\JoinColumn(name: 'artisan_id', nullable: false)]
    private Artisan $artisan;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $type = '';

    #[ORM\Column(type: Types::STRING, length: 1024)]
    private string $url = '';

    #[ORM\OneToOne(mappedBy: 'url', targetEntity: ArtisanUrlState::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ArtisanUrlState $state = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getArtisan(): Artisan
    {
        return $this->artisan;
    }

    public function setArtisan(Artisan $artisan): self
    {
        $this->artisan = $artisan;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getState(): ArtisanUrlState
    {
        return $this->state ?? $this->state = (new ArtisanUrlState())->setUrl($this);
    }

    public function setState(ArtisanUrlState $state): self
    {
        $this->state = $state;

        if ($this !== $state->getUrl()) {
            $state->setUrl($this);
        }

        return $this;
    }

    public function isDependency(): bool
    {
        return false;
    }

    public function recordSuccessfulFetch(): void
    {
        $this->getState()
            ->setLastSuccessUtc(UtcClock::now());
    }

    public function recordFailedFetch(int $code, string $reason): void
    {
        $this->getState()
            ->setLastFailureUtc(UtcClock::now())
            ->setLastFailureCode($code)
            ->setLastFailureReason($reason);
    }

    public function resetFetchResults(): void
    {
        $this->getState()
            ->setLastFailureUtc(null)
            ->setLastSuccessUtc(null)
            ->setLastFailureReason('')
            ->setLastFailureCode(0);
    }

    public function getOwnerName(): string
    {
        return $this->artisan->getName();
    }

    public function __toString(): string
    {
        return self::class.":$this->id:$this->url";
    }
}
