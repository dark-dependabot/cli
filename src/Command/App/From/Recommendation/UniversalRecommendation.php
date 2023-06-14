<?php

declare(strict_types=1);

namespace AcquiaMigrate\Recommendation;

use Closure;
use LogicException;

/**
 * Represents a recommendation that should be made for all sites.
 */
final class UniversalRecommendation extends DefinedRecommendation {

  /**
   * {@inheritDoc}
   */
  protected function __construct(string $package_name, string $version_constraint, array $install, bool $vetted, string $note, array $patches = []) {
    parent::__construct(Closure::fromCallable(function () {
      throw new LogicException(sprintf('It is nonsensical to call the applies() method on a % class instance.', __FUNCTION__, __CLASS__));
    }), $package_name, $version_constraint, $install, $vetted, $note, $patches);
  }

}
