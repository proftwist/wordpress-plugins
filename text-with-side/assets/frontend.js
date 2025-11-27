(function() {
    'use strict';

    function positionSideBlocks() {
        const sideBlocks = document.querySelectorAll('.text-with-side-block');

        sideBlocks.forEach(block => {
            // Находим родительский контейнер контента
            const contentContainer = block.closest('.entry-content, .post-content, .wp-block-post-content');
            if (!contentContainer) return;

            // Находим следующий элемент после нашего блока в потоке
            const nextElement = block.nextElementSibling;
            if (!nextElement) return;

            // Позиционируем блок относительно следующего элемента
            const nextElementRect = nextElement.getBoundingClientRect();
            const containerRect = contentContainer.getBoundingClientRect();
            const scrollTop = window.scrollY;

            // Вычисляем позицию по вертикали
            const topPosition = nextElementRect.top - containerRect.top + scrollTop;

            // Устанавливаем позицию
            block.style.top = topPosition + 'px';
        });
    }

    // Инициализация при загрузке
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', positionSideBlocks);
    } else {
        positionSideBlocks();
    }

    // Репозиционирование при изменении размера окна
    window.addEventListener('resize', positionSideBlocks);

    // Репозиционирование после загрузки изображений
    window.addEventListener('load', positionSideBlocks);

})();