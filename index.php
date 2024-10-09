<?php
/**
 * -- PHP Checklist App with Tailwind for UI & JSON for storage.
 */
// index.php
$filename = 'checklist.json';
$checklist = [];

// Load existing checklist if the file exists
if (file_exists($filename)) {
    $fileContent = file_get_contents($filename);
    // Check if file is not empty and decode JSON
    if ($fileContent) {
        $checklist = json_decode($fileContent, true) ?: [];
    }
}

// Save checklist to JSON file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents($filename, json_encode($_POST['checklist']));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist App</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .completed {
            text-decoration: line-through;
            color: gray;
        }
    </style>
</head>

<body class="bg-gray-100 p-6">
    <div class="container mx-auto">
        <h1 class="text-2xl font-bold mb-4">Checklist App</h1>
        <input type="text" id="newItem" class="border p-2 w-full mb-4" placeholder="Add a new checklist item..." />
        <ul id="checklist" class="list-group"></ul>
        <button id="deleteAll" class="bg-red-500 text-white rounded px-4 py-2 mt-4">Delete All</button>
    </div>

    <script>
        const checklist = <?php echo json_encode($checklist); ?>;
        const checklistElement = document.getElementById('checklist');
        const newItemInput = document.getElementById('newItem');
        const deleteAllButton = document.getElementById('deleteAll');

        // Load existing items on page load
        loadChecklist();

        function loadChecklist() {
            checklistElement.innerHTML = '';
            // Ensure checklist is an array
            if (Array.isArray(checklist)) {
                checklist.forEach((item, index) => addToChecklist(item, index));
            }
        }

        function addToChecklist(item, index) {
            const li = document.createElement('li');
            li.className = "flex items-center p-2 border-b";
            li.dataset.index = index;

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input mr-2';
            checkbox.checked = item.completed;
            checkbox.addEventListener('change', () => {
                item.completed = checkbox.checked;
                updateItemDisplay(li, item.completed);
                saveChecklist();
            });

            const span = document.createElement('span');
            span.className = item.completed ? 'completed item-text' : 'item-text';
            span.textContent = item.text;
            span.addEventListener('click', () => toggleCheckbox(li));

            const editInput = document.createElement('input');
            editInput.type = 'text';
            editInput.className = 'hidden edit-input border border-gray-300 rounded p-1 ml-2';
            editInput.value = item.text;
            editInput.addEventListener('blur', () => {
                item.text = editInput.value;
                span.textContent = item.text;
                span.classList.remove('hidden');
                editInput.classList.add('hidden');
                saveChecklist();
            });
            editInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    editInput.blur();
                }
            });

            const editButton = document.createElement('button');
            editButton.className = 'bg-yellow-500 text-white rounded px-2 py-1 text-sm ml-2';
            editButton.textContent = 'Edit';
            editButton.addEventListener('click', (e) => {
                e.stopPropagation();
                span.classList.add('hidden');
                editInput.classList.remove('hidden');
                editInput.focus();
            });

            const deleteButton = document.createElement('button');
            deleteButton.className = 'bg-red-500 text-white rounded px-2 py-1 text-sm ml-2';
            deleteButton.textContent = 'Delete';
            deleteButton.addEventListener('click', (e) => {
                e.stopPropagation();
                checklist.splice(index, 1);
                loadChecklist();
                saveChecklist();
            });

            li.appendChild(checkbox);
            li.appendChild(span);
            li.appendChild(editInput);
            li.appendChild(editButton);
            li.appendChild(deleteButton);

            // New container for buttons
            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'ml-auto flex-shrink-0';
            buttonContainer.appendChild(editButton);
            buttonContainer.appendChild(deleteButton);
            li.appendChild(buttonContainer);

            checklistElement.appendChild(li);
        }

        function toggleCheckbox(li) {
            const checkbox = li.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked; // Toggle checkbox status
            const index = li.dataset.index;
            checklist[index].completed = checkbox.checked;
            updateItemDisplay(li, checklist[index].completed);
            saveChecklist();
        }

        function updateItemDisplay(li, completed) {
            const span = li.querySelector('span');
            if (completed) {
                span.classList.add('completed');
            } else {
                span.classList.remove('completed');
            }
        }

        function saveChecklist() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ checklist })
            });
        }

        newItemInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && newItemInput.value.trim()) {
                const newItem = { text: newItemInput.value.trim(), completed: false };
                checklist.push(newItem);
                addToChecklist(newItem, checklist.length - 1);
                newItemInput.value = '';
                saveChecklist();
            }
        });

        deleteAllButton.addEventListener('click', () => {
            if (confirm('Are you sure you want to delete all items?')) {
                checklist.length = 0; // Clear the checklist array
                loadChecklist(); // Refresh the list display
                saveChecklist(); // Save the changes
            }
        });
    </script>
</body>

</html>