<?php

namespace App\Swagger\Schemas;

class FlightWithDates
{
    public \DateTime $scheduled_at;

    public \DateTimeImmutable $completed_at;

    public ?\DateTimeInterface $cancelled_at;

    public ?string $note;
}
