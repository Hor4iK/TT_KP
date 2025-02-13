<?php

session_start();

require_once 'config.php';


function redirect(string $path)
{
  header(header: "Location: $path");
  die();
}

function getPDO(): PDO
{
  try {
    return new \PDO(dsn: 'mysql:host=' . db_host . '; charset=utf8; dbname=' . db_name, username: db_username, password: db_password);
  } catch (\PDOException $e) {
    die("Connection error: {$e->getMessage()}");
  }
}


//VALIDATION


function clearValidation()
{
  $_SESSION['validation'] = [];
  // $_SESSION['class'] = null;
}
function setMessage(string $key, string $message): void
{
  $_SESSION['message'][$key] = $message;
}
function hasMessage(string $key): bool
{
  return isset($_SESSION['message'][$key]);
}
function getMessage(string $key): string
{
  $message = $_SESSION['message'][$key] ?? '';
  unset($_SESSION['message'][$key]);
  return $message;
}


//currentUser


function currentUser(): array|false
{
  $pdo = getPDO();

  if (!isset($_SESSION['user'])) {
    return false;
  }
  $userId = $_SESSION['user']['id'] ?? null;

  $stmt = $pdo->prepare(query: "SELECT * FROM users WHERE id = :id");
  $stmt->execute(['id' => $userId]);
  return $stmt->fetch(mode: \PDO::FETCH_ASSOC);
}
function currentUserAccount(): array
{
  $pdo = getPDO();

  $userId = $_SESSION['user']['id'] ?? null;

  $stmt = $pdo->prepare(query: "SELECT * FROM users WHERE id = :id");
  $stmt->execute(['id' => $userId]);
  return $stmt->fetch(mode: \PDO::FETCH_ASSOC);
}


//GOODS FUNCTIONS


function getGoods(int $Category): array
{
  $pdo = getPDO();

  if ($Category != 0)
    $result = $pdo->prepare(query: "SELECT goods.*, category.category_id, category.category_name
    FROM goods, category WHERE category.category_id = goods.category_id and category.category_id = $Category");
  else
    $result = $pdo->prepare(query: "SELECT * FROM goods");
  $result->execute();
  $products = array();

  while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
    $products[] = $product_info;
  };

  return $products;
}

function getGoodsPodcategory(int $Category, int $Podcategory): array
{
  $pdo = getPDO();

  if ($Category != 0 && $Podcategory != 0)
    $result = $pdo->prepare(query: "SELECT goods.*, category.category_id, category.category_name, podcategory.podcategory_id, podcategory.podcategory_name
    FROM goods, category, podcategory
    WHERE (category.category_id = goods.category_id and category.category_id = $Category) and (podcategory.podcategory_id = goods.good_podcategory_id and podcategory.podcategory_id = $Podcategory)");
  $result->execute();
  $products = array();

  while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
    $products[] = $product_info;
  };

  return $products;
}

function getCategory(): array
{
  $pdo = getPDO();
  $result = $pdo->prepare(query: "SELECT category_id, category_name, category_image FROM category");
  $result->execute();
  $category = array();

  while ($category_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
    $category[] = $category_info;
  };

  return $category;
}

function getPodcategory(int $Podcategory): array
{
  $pdo = getPDO();
  $result = $pdo->prepare(query: "SELECT podcategory.* FROM podcategory WHERE podcategory_id= $Podcategory");
  $result->execute();
  $podcategory = array();

  while ($podcategory_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
    $podcategory[] = $podcategory_info;
  };

  return $podcategory;
}

function getPodcategories(int $Category): array
{
  $pdo = getPDO();
  if ($Category != 0)
    $result = $pdo->prepare(query: "SELECT podcategory_id, podcategory_name, podcategory_image, podcategory_affiliation FROM podcategory WHERE podcategory_affiliation = $Category");
  else
    return null;
  $result->execute();
  $podcategory = array();

  while ($podcategory_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
    $podcategory[] = $podcategory_info;
  };

  return $podcategory;
}

function getGoodByID(int $idCard): array
{
  $pdo = getPDO();
  if ($idCard == null) {
    $idCard = $_COOKIE['idCard'];
  }
  $result = $pdo->prepare(query: "SELECT * FROM goods WHERE good_id = $idCard");
  $result->execute();
  $products = array();

  while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
    $products[] = $product_info;
  };
  return $products;
}

function deleteGood($idCard): string
{
  try {
    $pdo = getPDO();
    foreach ($idCard as $id) {
      $result = $pdo->prepare(query: "DELETE FROM goods WHERE good_id = $id");
      $result->execute();
    }
    return 'Successful deletion of objects';
  } catch (Exception $err) {
    return $err;
  }
}


//CART FUNCTIONS


function getCart(): array
{
  $user = $_SESSION['user']['id'];
  $products = array();
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT cart.cart_id, cart.user_id, cart.good_id, good_name, good_image, good_overview, good_provider, good_price, good_count, round((good_price * good_count), 2) as good_summ, good_unit from goods, cart WHERE goods.good_id = cart.good_id and user_id = $user and is_paid = 0");
    $result->execute();
    while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $products[] = $product_info;
    }
    return $products;
  } catch (Exception $err) {
    return $products[] = $err;
  }
}

function addGoodCartFromBtn(): string
{
  $idUser = $_SESSION['user']['id'];
  $idCard = $_COOKIE['idCard'];
  $products = array();
  try {
    $pdo = getPDO();

    $result = $pdo->prepare(query: "SELECT * FROM cart WHERE user_id = $idUser and good_id = $idCard and is_paid = 0");
    $result->execute();
    while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $products[] = $product_info;
    }

    if ($products != null) {
      $count = $products[0]['good_count'] + 1;
      $result = $pdo->prepare(query: "UPDATE cart SET good_count = $count WHERE user_id = $idUser and good_id = $idCard");
      $result->execute();
    } else {
      $result = $pdo->prepare(query: "INSERT INTO cart(user_id, good_id, good_count, is_paid) VALUES ($idUser, $idCard, 1, 0)");
      $result->execute();
    }

    return 'The product has been added to cart';
  } catch (Exception $err) {
    return $err;
  }
}

function addGoodCartFromPopup($countGood): string
{
  $idUser = $_SESSION['user']['id'];
  $idCard = $_COOKIE['idCard'];
  $products = array();
  try {
    $pdo = getPDO();

    $result = $pdo->prepare(query: "SELECT * FROM cart WHERE user_id = $idUser and good_id = $idCard and is_paid = 0");
    $result->execute();
    while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $products[] = $product_info;
    }

    if ($products != null) {
      $count = $products[0]['good_count'] + $countGood;
      $result = $pdo->prepare(query: "UPDATE cart SET good_count = $count WHERE user_id = $idUser and good_id = $idCard");
      $result->execute();
    } else {
      $result = $pdo->prepare(query: "INSERT INTO cart(user_id, good_id, good_count, is_paid) VALUES ($idUser, $idCard, $countGood, 0)");
      $result->execute();
    }

    return 'The product has been added to cart';
  } catch (Exception $err) {
    return $err;
  }
}

function deleteGoodFromCart($countGood): string
{
  $idUser = $_SESSION['user']['id'];
  $idCard = $_COOKIE['idCard'];
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "DELETE FROM cart WHERE user_id = $idUser and good_id = $idCard and good_count = $countGood");
    $result->execute();
    return 'The product has been deleted';
  } catch (Exception $err) {
  }
}

function getPaidGoods(): array
{
  $user = $_SESSION['user']['id'];
  $products = array();
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT cart.good_id, good_name, good_image, good_overview, good_provider, good_price, good_count, round((good_price * good_count), 2) as good_summ, good_unit from goods, cart WHERE goods.good_id = cart.good_id and user_id = $user and is_paid = 1");
    $result->execute();
    while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $products[] = $product_info;
    }
    return $products;
  } catch (Exception $err) {
    return $products[] = $err;
  }
}

function getAllPaidGoods(): array
{
  $products = array();
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT cart.good_id, cart.user_id, users.phone, users.name, good_name, good_image, good_overview, good_provider, good_price, good_count, round((good_price * good_count), 2) as good_summ, good_unit from goods, cart, users WHERE goods.good_id = cart.good_id and is_paid = 1 and users.id = cart.user_id");
    $result->execute();
    while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $products[] = $product_info;
    }
    return $products;
  } catch (Exception $err) {
    return $products[] = $err;
  }
}

function setPaidGood($cartId, $goodId, $userId): string
{
  // $user = $_SESSION['user']['id'];
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "UPDATE cart SET is_paid=1 WHERE cart_id=$cartId and user_id=$userId and good_id=$goodId");
    $result->execute();
    return 'The changes has been applied';
  } catch (Exception $err) {
    return $err;
  }
}

function changeGoodCart($goodId, $count): string
{
  $user = $_SESSION['user']['id'];
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "UPDATE cart SET good_count = $count WHERE user_id = $user and good_id=$goodId");
    $result->execute();
    return 'The changes has been applied';
  } catch (Exception $err) {
    return $err;
  }
}

function getGoodsBySearch(string $search): array
{
  $products = array();
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT * FROM goods WHERE good_name LIKE '%$search%'");
    $result->execute();
    while ($product_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $products[] = $product_info;
    };
    return $products;
  } catch (Exception $err) {
    return $products[] = $err;
  }
}

function getBannerTurn(): array
{
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT * FROM banners WHERE banner_turn=1");
    $result->execute();
    $banners = array();

    while ($banners_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $banners[] = $banners_info;
    };

    return $banners;
  } catch (Exception $err) {
    return $banners[] = $err;
  }
}

function getBanners(): array
{
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT * FROM banners ");
    $result->execute();
    $banners = array();

    while ($banners_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $banners[] = $banners_info;
    };

    return $banners;
  } catch (Exception $err) {
    return $banners[] = $err;
  }
}

function getMiniBanners(): array
{
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT * FROM mini_banners");
    $result->execute();
    $minibanners = array();

    while ($minibanners_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $minibanners[] = $minibanners_info;
    };

    return $minibanners;
  } catch (Exception $err) {
    return $minibanners[] = $err;
  }
}

function getOffers(): array
{
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT * FROM offers, goods WHERE offer_good_id = good_id");
    $result->execute();
    $offers = array();

    while ($offers_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $offers[] = $offers_info;
    };
    return $offers;
  } catch (Exception $err) {
    return $offers[] = $err;
  }
}

function getTurnedOffers(): array
{
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "SELECT * FROM offers, goods WHERE offer_good_turn=1 and offer_good_id = good_id");
    $result->execute();
    $offers = array();

    while ($offers_info = $result->fetch(mode: \PDO::FETCH_ASSOC)) {
      $offers[] = $offers_info;
    };
    return $offers;
  } catch (Exception $err) {
    return $offers[] = $err;
  }
}

function addOffers($idCards): array
{
  try {
    $pdo = getPDO();
    foreach ($idCards as $id) {
      $result = $pdo->prepare(query: "INSERT INTO offers(offer_good_id) VALUES ($id)");
      $result->execute();
    }

    return getOffers();
  } catch (Exception $err) {
    return $offers[] = $err;
  }
}

function deleteBanners($idBanner): string
{
  try {
    $pdo = getPDO();
    foreach ($idBanner as $id) {
      $result = $pdo->prepare(query: "DELETE FROM banners WHERE banner_id = $id");
      $result->execute();
    }
    return 'The banner has been deleted';
  } catch (Exception $err) {
    return $err;
  }
}

function addBanner(string $imageBanner, string $linkBanner, string $contentBanner): string
{
  try {
    $pdo = getPDO();
    $result = $pdo->prepare(query: "INSERT INTO banners (banner_image, banner_link, banner_content) VALUES ('$imageBanner', '$linkBanner', '$contentBanner')");
    $result->execute();
    return 'The new banner has been added';
  } catch (Exception $err) {
    return $err;
  }
}

function updateBanners($idBannersList, $obj): string
{
  try {
    $pdo = getPDO();
    foreach ($idBannersList as $id) {
      $result = $pdo->prepare(query: "UPDATE banners SET banner_turn= $obj[$id] WHERE banner_id= $id");
      $result->execute();
    }
    return 'The banners has been updated';
  } catch (Exception $err) {
    return $err;
  }
}

function updateMiniBanners($idMiniBannersList, $obj): string
{
  try {
    $pdo = getPDO();
    foreach ($idMiniBannersList as $id) {
      $result = $pdo->prepare(query: "UPDATE mini_banners SET mini_banner_name= '{$obj[$id]['name']}', mini_banner_src= '{$obj[$id]['src']}', mini_banner_image= '{$obj[$id]['image']}' WHERE mini_banner_id= $id");
      $result->execute();
    }
    return 'The minibanners has been updated';
  } catch (Exception $err) {
    return $err;
  }
}

function updateOffers($idOffersList, $obj): string
{
  try {
    $pdo = getPDO();
    foreach ($idOffersList as $id) {
      $result = $pdo->prepare(query: "UPDATE offers SET offer_good_turn= $obj[$id] WHERE offer_id= $id");
      $result->execute();
    }
    return 'The offers has been updated';
  } catch (Exception $err) {
    return $err;
  }
}

function deleteOffers($idOffers): string
{
  try {
    $pdo = getPDO();
    foreach ($idOffers as $id) {
      $result = $pdo->prepare(query: "DELETE FROM offers WHERE offer_id = $id");
      $result->execute();
    }
    return 'The offers has been deleted';
  } catch (Exception $err) {
    return $err;
  }
}
