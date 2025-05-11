<?php 
 session_start();
 require_once 'src\config\connect.php'; // Подключение к базе данных
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="Style/style.css?<? echo time()?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
</head>
<body>
    <? include 'blocks/menu.php';?>
<?php
    session_start();
    require_once 'src/config/connect.php';

    if (!isset($_SESSION['user'])) {
        header('Location: Side/auntification.php');
        exit();
    }

    $userId = $_SESSION['user']['id'];

    $sql = "
        SELECT p.*, c.id as cart_id, c.quantity 
        FROM cart c 
        JOIN Product p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ";

    $stmt = $connect->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $connect->error);
    }

    $stmt->bind_param("i", $userId);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $cartItems = $result->fetch_all(MYSQLI_ASSOC);

    $totalItems = 0;
    $totalPrise = 0;
    $totalDiscount = 0;

    foreach ($cartItems as $item) {
        $quantity = $item['quantity'] ?? 1;
        $price = ($item['sale_prise'] > 0 && $item['sale_prise'] < $item['prise']) 
                ? $item['sale_prise'] 
                : $item['prise'] ?? 0;
        $originalPrice = $item['prise'] ?? 0;
        
        $totalItems += $quantity;
        $totalPrise += $price * $quantity;
        $totalDiscount += ($originalPrice - $price) * $quantity;
    }
?>

    <div class="container" style="padding: 50px 35px;">
        <h1><i class="fa fa-shopping-cart"></i> Ваша корзина</h1>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Ваша корзина пуста</h2>
                <p>Добавьте товары из каталога, чтобы продолжить</p>
                <a href="index.php" class="checkout-btn">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items-grid">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-card" data-item-id="<?= $item['cart_id'] ?>">
                    <div class="card-image-container">
                        <img src="images/book/<?= htmlspecialchars($item['image'] ?? 'default.jpg') ?>" alt="Обложка книги" class="card-image">
                        <?php 
                        $hasDiscount = isset($item['prise'], $item['sale_prise']) && 
                                    $item['sale_prise'] > 0 && 
                                    $item['sale_prise'] < $item['prise'];
                        
                        if ($hasDiscount): ?>
                            <div class="card-badge">
                                -<?= round(100 - ($item['sale_prise'] / $item['prise'] * 100)) ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                        <div class="card-content">
                            <h3 class="card-title"><?= htmlspecialchars($item['name'] ?? '') ?></h3>
                            <p class="card-author"><?= htmlspecialchars($item['autor'] ?? '') ?></p>
                            
                            <div class="card-pricing">
                                <?php 
                                $hasDiscount = isset($item['prise'], $item['sale_prise']) && 
                                            $item['sale_prise'] > 0 && 
                                            $item['sale_prise'] < $item['prise'];
                                
                                if ($hasDiscount): ?>
                                    <span class="current-price"><?= $item['sale_prise'] ?> ₽</span>
                                    <span class="original-price"><?= $item['prise'] ?> ₽</span>
                                <?php else: ?>
                                    <span class="current-price"><?= $item['prise'] ?? 0 ?> ₽</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-quantity">
                                <button class="quantity-btn minus">-</button>
                                <input type="number" value="<?= $item['quantity'] ?? 1 ?>" min="1" class="quantity-input">
                                <button class="quantity-btn plus">+</button>
                            </div>
                            
                            <div class="card-subtotal">
                                Итого: <span class="item-total" 
                                    data-single-price="<?= (isset($item['sale_prise']) && $item['sale_prise'] > 0) ? $item['sale_prise'] : ($item['prise'] ?? 0) ?>">
                                    <?= ((isset($item['sale_prise']) && $item['sale_prise'] > 0) ? $item['sale_prise'] : ($item['prise'] ?? 0)) * ($item['quantity'] ?? 1) ?> ₽
                                </span>
                            </div>
                            
                            <button class="remove-item"><i class="fa fa-trash"></i> Удалить</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary-card">
                    <div class="summary-title">Итог заказа</div>
                    <div class="summary-content">
                        <div class="summary-row">
                            <span class="items-count">Товары (<?= $totalItems ?>)</span>
                            <span class="items-price"><?= number_format($totalPrise, 2) ?> ₽</span>
                        </div>
                        <?php if ($totalDiscount > 0): ?>
                            <div class="summary-row discount-row">
                                <span>Скидка</span>
                                <span class="discount-amount">-<?= number_format($totalDiscount, 2) ?> ₽</span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row">
                            <span>Доставка</span>
                            <span>Бесплатно</span>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-row summary-total">
                            <span>Итого</span>
                            <span class="total-price"><?= number_format($totalPrise, 2) ?> ₽</span>
                        </div>
                        <button class="checkout-btn">Оформить заказ</button>
                        <a href="index.php" class="continue-shopping"><i class="fa fa-arrow-left"></i> Продолжить покупки</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
// Функция для обновления суммы за одну позицию
function updateItemTotal(itemElement) {
    const quantityInput = itemElement.querySelector('.quantity-input');
    const totalElement = itemElement.querySelector('.item-total');
    const singlePrice = parseFloat(totalElement.dataset.singlePrice);
    
    const quantity = parseInt(quantityInput.value) || 1;
    const totalPrice = singlePrice * quantity;
    
    totalElement.textContent = totalPrice.toFixed(2) + ' ₽';
    
    // Обновляем подытог в карточке
    const cardSubtotal = itemElement.querySelector('.card-subtotal .item-total');
    if (cardSubtotal) {
        cardSubtotal.textContent = totalPrice.toFixed(2) + ' ₽';
    }
    
    return {
        quantity: quantity,
        totalPrice: totalPrice,
        discount: 0 // Будем рассчитывать в основной функции
    };
}

// Функция для расчета скидки для одного товара
function calculateItemDiscount(itemElement) {
    const originalPriceElement = itemElement.querySelector('.original-price');
    if (!originalPriceElement) return 0;
    
    const originalPrice = parseFloat(originalPriceElement.textContent);
    const currentPrice = parseFloat(itemElement.querySelector('.current-price').textContent);
    const quantity = parseInt(itemElement.querySelector('.quantity-input').value) || 1;
    
    // Если текущая цена меньше оригинальной - считаем скидку
    return currentPrice < originalPrice ? (originalPrice - currentPrice) * quantity : 0;
}

// Функция для обновления всех итогов
function updateCartTotals() {
    let totalItems = 0;
    let totalPrice = 0;
    let totalDiscount = 0;
    let totalWithoutDiscount = 0;
    
    document.querySelectorAll('.cart-card').forEach(item => {
        // Обновляем сумму за позицию и добавляем к общим
        const itemData = updateItemTotal(item);
        const itemDiscount = calculateItemDiscount(item);
        
        totalItems += itemData.quantity;
        totalPrice += itemData.totalPrice;
        totalDiscount += itemDiscount;
        totalWithoutDiscount += (itemData.totalPrice + itemDiscount);
    });
    
    // Обновляем блок с итогами
    document.querySelector('.items-count').textContent = `Товары (${totalItems})`;
    document.querySelector('.items-price').textContent = totalWithoutDiscount.toFixed(2) + ' ₽';
    
    // Обновляем скидку (если есть)
    const discountRow = document.querySelector('.discount-row');
    if (totalDiscount > 0) {
        if (!discountRow) {
            // Создаем строку скидки, если ее нет
            const summaryContent = document.querySelector('.summary-content');
            const itemsRow = document.querySelector('.summary-row.items-count');
            
            const newDiscountRow = document.createElement('div');
            newDiscountRow.className = 'summary-row discount-row';
            newDiscountRow.innerHTML = `
                <span>Скидка</span>
                <span class="discount-amount">-${totalDiscount.toFixed(2)} ₽</span>
            `;
            
            summaryContent.insertBefore(newDiscountRow, itemsRow.nextSibling);
        } else {
            // Обновляем существующую скидку
            discountRow.querySelector('.discount-amount').textContent = `-${totalDiscount.toFixed(2)} ₽`;
        }
    } else if (discountRow) {
        // Удаляем строку скидки, если скидок нет
        discountRow.remove();
    }
    
    // Обновляем итоговую сумму
    document.querySelector('.total-price').textContent = totalPrice.toFixed(2) + ' ₽';
    
    // Обновляем счетчик в шапке
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = totalItems;
    }
}

// Функция для обновления количества на сервере
async function updateCartItemOnServer(itemId, quantity) {
    try {
        const response = await fetch('src/actions/update_cart_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${itemId}&quantity=${quantity}`
        });
        
        return await response.json();
    } catch (error) {
        console.error('Ошибка при обновлении товара:', error);
        return { success: false };
    }
}

// Функция для удаления товара
async function removeCartItem(itemId, itemElement) {
    try {
        const response = await fetch('src/actions/remove_cart_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${itemId}`
        });
        
        const data = await response.json();
        if (data.success) {
            // Анимация удаления
            itemElement.style.transition = 'all 0.3s ease';
            itemElement.style.opacity = '0';
            itemElement.style.transform = 'scale(0.8)';
            itemElement.style.margin = '0';
            itemElement.style.padding = '0';
            itemElement.style.height = '0';
            itemElement.style.overflow = 'hidden';
            
            setTimeout(() => {
                itemElement.remove();
                updateCartTotals();
                
                // Если корзина пуста, показываем сообщение
                if (document.querySelectorAll('.cart-card').length === 0) {
                    location.reload(); // Перезагружаем страницу для показа пустой корзины
                }
            }, 300);
        }
        return data.success;
    } catch (error) {
        console.error('Ошибка при удалении товара:', error);
        return false;
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Обработчики для кнопок "+"
    document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
        btn.addEventListener('click', async function() {
            const itemElement = this.closest('.cart-card');
            const input = itemElement.querySelector('.quantity-input');
            const itemId = itemElement.dataset.itemId;
            
            let quantity = parseInt(input.value) || 1;
            quantity++;
            input.value = quantity;
            
            // Локальное обновление
            updateCartTotals();
            
            // Синхронизация с сервером
            const result = await updateCartItemOnServer(itemId, quantity);
            if (!result.success) {
                input.value = quantity - 1;
                updateCartTotals();
            }
        });
    });
    
    // Обработчики для кнопок "-"
    document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
        btn.addEventListener('click', async function() {
            const itemElement = this.closest('.cart-card');
            const input = itemElement.querySelector('.quantity-input');
            const itemId = itemElement.dataset.itemId;
            
            let quantity = parseInt(input.value) || 1;
            if (quantity > 1) {
                quantity--;
                input.value = quantity;
                
                // Локальное обновление
                updateCartTotals();
                
                // Синхронизация с сервером
                const result = await updateCartItemOnServer(itemId, quantity);
                if (!result.success) {
                    input.value = quantity + 1;
                    updateCartTotals();
                }
            }
        });
    });
    
    // Обработчики для ручного ввода количества
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', async function() {
            const itemElement = this.closest('.cart-card');
            const itemId = itemElement.dataset.itemId;
            let quantity = parseInt(this.value) || 1;
            
            if (quantity < 1) {
                quantity = 1;
                this.value = quantity;
            }
            
            // Локальное обновление
            updateCartTotals();
            
            // Синхронизация с сервером
            const result = await updateCartItemOnServer(itemId, quantity);
            if (!result.success) {
                this.value = this.dataset.prevValue || 1;
                updateCartTotals();
            } else {
                this.dataset.prevValue = quantity;
            }
        });
        
        // Сохраняем начальное значение
        input.dataset.prevValue = input.value;
    });
    
    // Обработчики для кнопок удаления
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemElement = this.closest('.cart-card');
            const itemId = itemElement.dataset.itemId;
            
            if (confirm('Вы уверены, что хотите удалить этот товар из корзины?')) {
                removeCartItem(itemId, itemElement);
            }
        });
    });
    
    // Первоначальное обновление всех сумм
    updateCartTotals();
});
</script>
    
<? include 'blocks/footer.php';?>
</body>
</html>