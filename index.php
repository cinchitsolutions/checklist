<?php
// Define the path to the JSON file
$jsonFile = 'checklist.json';

// Check if the JSON file exists; if not, create it
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([]));
}

// Read the checklist from the JSON file
$checklist = json_decode(file_get_contents($jsonFile), true);

// Ensure $checklist is an array
if (!is_array($checklist)) {
    $checklist = [];
}

// Handle AJAX request to save checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents('php://input');
    file_put_contents($jsonFile, $data);
    exit; // Stop further processing after saving
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .completed {
            text-decoration: line-through;
            color: #999; /* Optional: change the color of completed tasks */
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Checklist</h1>
        <div class="input-group mb-3">
            <input type="text" id="itemInput" class="form-control" placeholder="Add new item">
            <button id="addItem" class="btn btn-primary">Add</button>
        </div>
        <ul id="checklist" class="list-group mb-3">
            <?php foreach ($checklist as $index => $item): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center" data-index="<?= $index ?>">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" <?= $item['completed'] ? 'checked' : '' ?>>
                        <span class="<?= $item['completed'] ? 'completed' : '' ?>"><?= htmlspecialchars($item['text']) ?></span>
                    </div>
                    <div>
                        <button class="btn btn-warning btn-sm edit-btn">Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn">Delete</button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <button id="selectAll" class="btn btn-secondary">Select All</button>
        <button id="deleteAll" class="btn btn-danger">Delete All Items</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const input = document.getElementById('itemInput');

        // Function to add an item to the checklist
        function addItem() {
            const text = input.value.trim();
            if (text) {
                const item = { text: text, completed: false };
                addToChecklist(item);
                input.value = '';
            }
        }

        // Event listener for adding item on Enter key press
        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                addItem();
            }
        });

        document.getElementById('addItem').addEventListener('click', addItem);

        function addToChecklist(item) {
            const checklist = document.getElementById('checklist');
            const li = document.createElement('li');
            const index = checklist.children.length; // Current index for the new item
            li.dataset.index = index;
            li.className = "list-group-item d-flex justify-content-between align-items-center";

            const checkbox = document.createElement('input');
            checkbox.className = 'form-check-input';
            checkbox.type = 'checkbox';
            checkbox.addEventListener('change', function() {
                item.completed = this.checked;
                updateItemDisplay(li, item.completed);
                saveChecklist();
            });

            const span = document.createElement('span');
            span.textContent = item.text;

            // Edit button
            const editButton = document.createElement('button');
            editButton.textContent = 'Edit';
            editButton.classList.add('btn', 'btn-warning', 'btn-sm', 'edit-btn');
            editButton.addEventListener('click', function() {
                const newText = prompt('Edit item:', item.text);
                if (newText) {
                    item.text = newText;
                    span.textContent = newText;
                    saveChecklist();
                }
            });

            // Delete button
            const deleteButton = document.createElement('button');
            deleteButton.textContent = 'Delete';
            deleteButton.classList.add('btn', 'btn-danger', 'btn-sm', 'delete-btn');
            deleteButton.addEventListener('click', function() {
                checklist.removeChild(li);
                saveChecklist(); // Update the JSON file after deletion
            });

            li.appendChild(checkbox);
            li.appendChild(span);
            li.appendChild(editButton);
            li.appendChild(deleteButton);
            checklist.appendChild(li);
            
            saveChecklist(); // Save after adding
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
            const items = [];
            document.querySelectorAll('#checklist li').forEach(li => {
                const checkbox = li.querySelector('input[type="checkbox"]');
                items.push({
                    text: li.querySelector('span').textContent,
                    completed: checkbox.checked
                });
            });

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(items)
            });
        }

        // Load existing items and add event listeners for edit and delete
        document.querySelectorAll('#checklist li').forEach(li => {
            const checkbox = li.querySelector('input[type="checkbox"]');
            checkbox.addEventListener('change', function() {
                const index = li.dataset.index;
                const item = {
                    text: li.querySelector('span').textContent,
                    completed: this.checked
                };
                updateItemDisplay(li, item.completed);
                saveChecklist();
            });

            // Add event listeners for edit and delete buttons
            li.querySelector('.edit-btn').addEventListener('click', function() {
                const newText = prompt('Edit item:', li.querySelector('span').textContent);
                if (newText) {
                    li.querySelector('span').textContent = newText;
                    saveChecklist();
                }
            });

            li.querySelector('.delete-btn').addEventListener('click', function() {
                li.parentNode.removeChild(li);
                saveChecklist();
            });
        });

        // Add event listener for Delete All button
        document.getElementById('deleteAll').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete all items?')) {
                const checklist = document.getElementById('checklist');
                checklist.innerHTML = ''; // Clear all items from the UI
                saveChecklist(); // Update the JSON file
            }
        });

        // Add event listener for Select All button
        document.getElementById('selectAll').addEventListener('click', function() {
            if (confirm('Are you sure you want to select/deselect all items?')) {
                const checkboxes = document.querySelectorAll('#checklist input[type="checkbox"]');
                const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked; // Check if all are checked to toggle
                    checkbox.dispatchEvent(new Event('change')); // Trigger change event
                });
            }
        });

        // Load existing items on page load
        loadChecklist();
    </script>
</body>
</html>
