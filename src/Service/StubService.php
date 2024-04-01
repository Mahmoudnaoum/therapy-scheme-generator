<?php

namespace App\Service;

use App\Repository\Therapy\StubRepository;

class StubService
{

  private $stubRepository;

  public function __construct(StubRepository $stubRepository)
  {
    $this->stubRepository = $stubRepository;
  }

  public function getSchemesByStubId($stubId)
  {
      return $this->stubRepository->findSchemesByStubId($stubId);
  }
}
