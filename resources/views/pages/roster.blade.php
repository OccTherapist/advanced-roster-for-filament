<x-filament-panels::page>
    <x-advanced-roster::roster.mobile />
    <x-advanced-roster::roster.desktop />

    @push('styles')
        <style>
            .roster-drop-zone {
                transition: all 200ms ease-in-out;
                outline: 2px solid transparent;
            }

            .roster-drop-zone.drag-over {
                outline: 2px dashed rgb(var(--primary-500));
                background-color: rgb(var(--primary-500) / 0.1);
                box-shadow: 0 10px 15px -3px rgb(var(--primary-600) / 0.2);
                transform: scale(1.05);
            }

            .roster-day [draggable="true"] {
                transition: all 200ms ease-in-out;
                user-select: none;
            }

            .roster-day [draggable="true"]:hover {
                box-shadow: 0 4px 6px -1px rgb(var(--primary-600) / 0.2);
            }

            .dragging {
                transition: all 200ms ease-in-out;
                opacity: 0.4;
                transform: scale(0.9);
                filter: grayscale(1);
            }

            .roster-day-column {
                transition: opacity 200ms ease-in-out;
            }

            .dragging-assignee-mode .roster-day-column {
                opacity: 0.3;
                pointer-events: none;
            }

            .dragging-assignee {
                border: 2px dashed rgb(var(--primary-500));
                background-color: rgb(var(--primary-500) / 0.1);
                box-shadow: 0 10px 15px -3px rgb(var(--primary-600) / 0.2);
                outline: 1px solid rgb(var(--primary-500));
            }

            .dragging-assignee-mode .roster-assignee-row:not(.dragging-assignee) {
                border: 1px dashed rgb(var(--primary-500) / 0.75);
            }

            .drag-hover-assignee {
                background-color: rgb(var(--primary-500) / 0.05) !important;
                transform: translateY(-1px);
            }

            .roster-assignee-row.insertion-indicator-top {
                border-top: 3px solid rgb(var(--primary-500)) !important;
                margin-top: -4px;
            }

            .roster-assignee-row.insertion-indicator-bottom {
                border-bottom: 3px solid rgb(var(--primary-500)) !important;
                margin-bottom: -4px;
            }

            .roster-assignee-row {
                transition: all 200ms ease-in-out;
            }
        </style>
    @endpush

    <script>
        var draggedElement = null;
        var draggedData = null;

        function parseAssigneeId(id) {
            if (id === null || id === undefined || id === 'null' || id === '') {
                return null;
            }

            return /^\d+$/.test(String(id)) ? parseInt(id, 10) : id;
        }

        function handleDragStart(event) {
            draggedElement = event.target;
            draggedData = {
                entryId: event.target.dataset.entryId,
                assigneeType: event.target.dataset.assigneeType,
                assigneeId: event.target.dataset.assigneeId,
                date: event.target.dataset.date,
            };

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', event.target.outerHTML);

            event.target.classList.add('dragging');

            setTimeout(_ => event.target.style.pointerEvents = 'none', 0);
        }

        function handleDragOver(event) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
        }

        function handleDragEnter(event) {
            event.preventDefault();
            const dropZone = event.target.closest('.roster-drop-zone');

            if (!dropZone) {
                return;
            }

            clearAllDropZoneStyles();
            dropZone.classList.add('drag-over');
        }

        function handleDragLeave(event) {
            event.preventDefault();
            const dropZone = event.target.closest('.roster-drop-zone');

            if (!dropZone) {
                return;
            }

            const rect = dropZone.getBoundingClientRect();
            const x = event.clientX;
            const y = event.clientY;

            if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                dropZone.classList.remove('drag-over');
                dropZone.style.backgroundColor = '';
                dropZone.style.border = '';
            }
        }

        function handleDrop(event) {
            event.preventDefault();

            const dropZone = event.target.classList.contains('roster-drop-zone')
                ? event.target
                : event.target.closest('.roster-drop-zone');

            if (!dropZone || !draggedData) {
                return;
            }

            clearAllDropZoneStyles();

            const newDate = dropZone.dataset.date;
            const newAssigneeType = dropZone.dataset.assigneeType;
            const newAssigneeId = dropZone.dataset.assigneeId === 'null' ? null : dropZone.dataset.assigneeId;

            if (
                draggedData.date === newDate
                && draggedData.assigneeType === newAssigneeType
                && String(draggedData.assigneeId) === String(newAssigneeId)
            ) {
                return;
            }

            Livewire.dispatch('request-move-entry', {
                entryId: parseInt(draggedData.entryId, 10),
                date: newDate,
                assigneeType: newAssigneeType,
                assigneeId: parseAssigneeId(newAssigneeId),
            });
        }

        function clearAllDropZoneStyles() {
            document.querySelectorAll('.roster-drop-zone').forEach(zone => {
                zone.classList.remove('drag-over');
                zone.style.backgroundColor = '';
                zone.style.border = '';
            });
        }

        document.addEventListener('dragend', function () {
            if (draggedElement) {
                draggedElement.classList.remove('dragging');
                draggedElement.style.opacity = '';
                draggedElement.style.pointerEvents = '';
                draggedElement = null;
                draggedData = null;
            }

            clearAllDropZoneStyles();
        });

        var draggedAssigneeElement = null;
        var currentDragType = null;

        function handleAssigneeDragStart(event) {
            draggedAssigneeElement = event.target.closest('.roster-assignee-row');
            currentDragType = 'assignee';

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', draggedAssigneeElement.outerHTML);

            draggedAssigneeElement.classList.add('dragging-assignee');
            draggedAssigneeElement.style.opacity = '0.3';
            draggedAssigneeElement.style.zIndex = '1000';

            document.body.classList.add('dragging-assignee-mode');
        }

        function handleAssigneeDragOver(event) {
            event.preventDefault();

            if (currentDragType !== 'assignee') {
                return;
            }

            event.dataTransfer.dropEffect = 'move';

            const dropTarget = event.target.closest('.roster-assignee-row');

            if (dropTarget && dropTarget !== draggedAssigneeElement) {
                document.querySelectorAll('.drag-hover-assignee, .insertion-indicator-top, .insertion-indicator-bottom').forEach(el => {
                    el.classList.remove('drag-hover-assignee', 'insertion-indicator-top', 'insertion-indicator-bottom');
                });

                const rect = dropTarget.getBoundingClientRect();
                const insertBefore = event.clientY < rect.top + rect.height / 2;

                dropTarget.classList.add('drag-hover-assignee');

                if (insertBefore) {
                    dropTarget.classList.add('insertion-indicator-top');
                } else {
                    dropTarget.classList.add('insertion-indicator-bottom');
                }
            }
        }

        function handleAssigneeDrop(event) {
            event.preventDefault();

            if (!draggedAssigneeElement || currentDragType !== 'assignee') {
                return;
            }

            const dropTarget = event.target.closest('.roster-assignee-row');

            if (!dropTarget || dropTarget === draggedAssigneeElement) {
                return;
            }

            const draggedWrapper = draggedAssigneeElement.closest('.contents');
            const targetWrapper = dropTarget.closest('.contents');

            if (!draggedWrapper || !targetWrapper) {
                return;
            }

            const assigneeContainer = targetWrapper.parentElement;
            const rect = dropTarget.getBoundingClientRect();
            const insertBefore = event.clientY < rect.top + rect.height / 2;

            draggedAssigneeElement.style.transition = 'all 0.2s ease';

            if (insertBefore) {
                assigneeContainer.insertBefore(draggedWrapper, targetWrapper);
            } else {
                assigneeContainer.insertBefore(draggedWrapper, targetWrapper.nextElementSibling);
            }

            updateAssigneeOrder();
        }

        function updateAssigneeOrder() {
            const assigneeRows = document.querySelectorAll('.roster-assignee-row');
            const assigneeIds = Array.from(assigneeRows).map(row => parseAssigneeId(row.dataset.assigneeId));

            @this.call('updateAssigneeOrder', assigneeIds);
        }

        document.addEventListener('dragend', function () {
            if (draggedAssigneeElement) {
                draggedAssigneeElement.classList.remove('dragging-assignee');
                draggedAssigneeElement.style.opacity = '';
                draggedAssigneeElement.style.transform = '';
                draggedAssigneeElement.style.zIndex = '';
                draggedAssigneeElement.style.transition = '';
                draggedAssigneeElement = null;
            }

            document.body.classList.remove('dragging-assignee-mode');

            document.querySelectorAll('.drag-hover-assignee, .insertion-indicator-top, .insertion-indicator-bottom').forEach(el => {
                el.classList.remove('drag-hover-assignee', 'insertion-indicator-top', 'insertion-indicator-bottom');
            });

            currentDragType = null;
        });
    </script>

    <x-filament-actions::modals />
</x-filament-panels::page>
