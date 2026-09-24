<?php

namespace Cadud\Helpers\Html\DTO;

final class ViewHead
{
  public string $title = "";
  public string $description = "";
  public string $author = "";
  public string $keywords = "";
  public string $canonical = "";
  public string $robots = "index, follow";
  public string $language = "pt-BR";
  public string $geoRegion = "BR-SP";

  public string $ogTitle = "";
  public string $ogDescription = "";
  public string $ogImage = "";
  public string $ogUrl = "";
  public string $ogType = "website";
  public string $ogLocale = "pt_BR";
  public string $ogSiteName = "";

  public string $twitterCard = "summary_large_image";
  public string $twitterTitle = "";
  public string $twitterDescription = "";
  public string $twitterImage = "";

  public bool $allowIndex = true;

  public function title(string $title): static
  {
    $this->title = $title;
    return $this;
  }

  public function description(string $description): static
  {
    $this->description = $description;
    return $this;
  }

  public function author(string $author): static
  {
    $this->author = $author;
    return $this;
  }

  public function keywords(string $keywords): static
  {
    $this->keywords = $keywords;
    return $this;
  }

  public function canonical(string $canonical): static
  {
    $this->canonical = $canonical;
    return $this;
  }

  public function allowIndex(bool $allow = true): static
  {
    $this->allowIndex = $allow;
    return $this;
  }

  public function noIndex(): static
  {
    $this->allowIndex = false;
    return $this;
  }

  public function og(string $title = "", string $description = "", string $image = "", string $url = ""): static
  {
    $this->ogTitle = $title;
    $this->ogDescription = $description;
    $this->ogImage = $image;
    $this->ogUrl = $url;
    return $this;
  }

  public function twitter(string $title = "", string $description = "", string $image = ""): static
  {
    $this->twitterTitle = $title;
    $this->twitterDescription = $description;
    $this->twitterImage = $image;
    return $this;
  }

  /**
   * Monta o array final pra view, aplicando fallbacks:
   * OG herda de title/description, Twitter herda de OG,
   * e robots é definido com base no allowIndex.
   */
  public function toArray(): array
  {
    $ogTitle = $this->ogTitle !== "" ? $this->ogTitle : $this->title;
    $ogDescription = $this->ogDescription !== "" ? $this->ogDescription : $this->description;
    $ogImage = $this->ogImage;
    $ogUrl = $this->ogUrl !== "" ? $this->ogUrl : $this->canonical;

    $robots = $this->allowIndex ? $this->robots : "noindex, nofollow";

    return [
      "title" => $this->title,
      "description" => $this->description,
      "author" => $this->author,
      "keywords" => $this->keywords,
      "canonical" => $this->canonical,
      "robots" => $robots,
      "language" => $this->language,
      "geo_region" => $this->geoRegion,

      "og_title" => $ogTitle,
      "og_description" => $ogDescription,
      "og_image" => $ogImage,
      "og_url" => $ogUrl,
      "og_type" => $this->ogType,
      "og_locale" => $this->ogLocale,
      "og_site_name" => $this->ogSiteName,

      "twitter_card" => $this->twitterCard,
      "twitter_title" => $this->twitterTitle !== "" ? $this->twitterTitle : $ogTitle,
      "twitter_description" => $this->twitterDescription !== "" ? $this->twitterDescription : $ogDescription,
      "twitter_image" => $this->twitterImage !== "" ? $this->twitterImage : $ogImage,
    ];
  }
}