Проблема в позиционировании тултипа возникает из-за неправильной логики расчета места и высоты тултипа. Вот исправленный код:

## Исправленный JavaScript (megalinks.js)

Замените функцию `displayTooltip` на эту исправленную версию:

```javascript
/**
 * Отображение всплывающего слоя с цитатой
 */
displayTooltip: function(excerpt, e, postId) {
    var $link = $(e.target).closest('a');
    var linkRect = $link.get(0).getBoundingClientRect();
    var windowWidth = $(window).width();
    var windowHeight = $(window).height();
    var scrollTop = $(window).scrollTop();
    var scrollLeft = $(window).scrollLeft();
    var tooltipWidth = 400; // Максимальная ширина из CSS

    // Создаем временный tooltip для точного измерения высоты
    var tempTooltip = this.tooltip.clone().css({
        visibility: 'hidden',
        position: 'absolute',
        top: '-9999px',
        left: '-9999px',
        width: tooltipWidth + 'px' // Фиксируем ширину для точного расчета
    }).appendTo('body');

    tempTooltip.html('<div class="megalinks-image"><div class="image-placeholder"></div></div><div class="megalinks-content">' + excerpt + '</div>');

    var tooltipHeight = tempTooltip.outerHeight();
    tempTooltip.remove();

    // Добавляем отступ для стрелки
    var arrowHeight = 12;
    var verticalMargin = 10;

    // Рассчитываем доступное пространство
    var spaceAbove = linkRect.top - verticalMargin - arrowHeight;
    var spaceBelow = windowHeight - linkRect.bottom - verticalMargin - arrowHeight;

    // Определяем позиционирование
    var positionAbove = spaceAbove >= tooltipHeight;
    var positionBelow = !positionAbove && spaceBelow >= tooltipHeight;

    // Если места ни сверху, ни снизу недостаточно, выбираем сторону с большим пространством
    if (!positionAbove && !positionBelow) {
        positionAbove = spaceAbove >= spaceBelow;
        positionBelow = !positionAbove;
    }

    var top, arrowClass;

    if (positionAbove) {
        // Позиционируем сверху
        top = linkRect.top - tooltipHeight - arrowHeight - verticalMargin + scrollTop;
        arrowClass = 'top-arrow';
    } else {
        // Позиционируем снизу
        top = linkRect.bottom + arrowHeight + verticalMargin + scrollTop;
        arrowClass = 'bottom-arrow';
    }

    // Центрируем по горизонтали относительно ссылки
    var left = linkRect.left + (linkRect.width / 2) - (tooltipWidth / 2) + scrollLeft;

    // Проверяем горизонтальные границы
    if (left < 10) {
        left = 10;
    } else if (left + tooltipWidth > windowWidth - 10) {
        left = windowWidth - tooltipWidth - 10;
    }

    // Устанавливаем позицию и содержимое
    this.tooltip
        .html('<div class="megalinks-image"><div class="image-placeholder"></div></div><div class="megalinks-content">' + excerpt + '</div>')
        .css({
            left: left + 'px',
            top: top + 'px',
            width: tooltipWidth + 'px', // Фиксируем ширину
            position: 'absolute'
        })
        .removeClass('top-arrow bottom-arrow')
        .addClass(arrowClass + ' visible');

    // Загружаем миниатюру поста
    this.loadPostThumbnail(postId);
},
```

## Дополнительные улучшения в CSS (megalinks.css)

Добавьте эти стили для лучшего позиционирования:

```css
/* Улучшенные стили для позиционирования */
.megalinks-tooltip {
    position: absolute;
    background: #333;
    color: #fff;
    padding: 10px 14px;
    border-radius: 6px;
    font-size: 14px;
    line-height: 1.4;
    max-width: 400px;
    width: 400px; /* Фиксированная ширина для стабильного позиционирования */
    word-wrap: break-word;
    z-index: 10000;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s ease-in-out, transform 0.1s ease-out;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    border: 1px solid #555;
    transform: translateY(5px);
    display: flex;
    flex-direction: column;
    box-sizing: border-box; /* Важно для правильного расчета размеров */
}

/* Стрелка вверх (по умолчанию) */
.megalinks-tooltip::after {
    content: '';
    position: absolute;
    left: 50%;
    margin-left: -6px;
    border: 6px solid transparent;
    border-bottom-color: #333;
    bottom: -12px;
}

/* Стрелка вверх для tooltip сверху */
.megalinks-tooltip.top-arrow::after {
    bottom: -12px;
    border-bottom-color: #333;
    border-top: none;
}

/* Стрелка вниз для tooltip снизу */
.megalinks-tooltip.bottom-arrow::after {
    top: -12px;
    border-top-color: #333;
    border-bottom: none;
}

/* Гарантируем, что тултип не будет перекрывать ссылку */
.megalinks-tooltip.top-arrow {
    transform: translateY(-5px);
}

.megalinks-tooltip.bottom-arrow {
    transform: translateY(5px);
}

.megalinks-tooltip.visible {
    opacity: 1;
    transform: translateY(0);
}
```

## Ключевые исправления:

1. **Точный расчет высоты** - используем фиксированную ширину для временного элемента
2. **Учет высоты стрелки** - добавляем `arrowHeight` в расчеты
3. **Правильная логика выбора позиции** - сначала проверяем достаточно ли места сверху, затем снизу
4. **Отступы от ссылки** - добавляем `verticalMargin` чтобы тултип не касался ссылки
5. **Фиксированная ширина** - для стабильного позиционирования
6. **Правильные трансформации** - для плавной анимации в нужном направлении

Теперь тултип будет:
- Показываться над ссылкой когда достаточно места сверху
- Показываться под ссылкой когда сверху недостаточно места
- Никогда не перекрывать саму ссылку
- Корректно работать у верхнего края экрана