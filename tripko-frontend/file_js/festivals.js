// File: festivals.js
document.addEventListener('DOMContentLoaded', function() {
    loadFestivals();
    loadMunicipalities();
});

let festivals = [];

async function loadFestivals() {
    try {
        const response = await fetch('../../tripko-backend/api/festival/read.php');
        const data = await response.json();
        
        if (data.success === false) {
            throw new Error(data.message);
        }
        
        festivals = data;
        displayFestivals(festivals);
    } catch (error) {
        console.error('Error loading festivals:', error);
        alert('Error loading festivals. Please try again.');
    }
}

async function loadMunicipalities() {
    try {
        const response = await fetch('../../tripko-backend/api/towns/read.php');
        const data = await response.json();
        
        if (!Array.isArray(data)) {
            throw new Error('Invalid response from server');
        }

        const select = document.querySelector('select[name="municipality"]');
        data.forEach(town => {
            const option = document.createElement('option');
            option.value = town.town_id;
            option.textContent = town.town_name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading municipalities:', error);
        alert('Error loading municipalities. Please try again.');
    }
}

function displayFestivals(festivals) {
    const grid = document.getElementById('festivalsGrid');
    grid.innerHTML = '';
    
    festivals.forEach(festival => {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-lg shadow overflow-hidden';
        
        const imagePath = festival.image_path 
            ? `../../uploads/${festival.image_path}`
            : '../images/default-festival.jpg';
        
        card.innerHTML = `
            <img src="${imagePath}" alt="${festival.name}" class="w-full h-48 object-cover">
            <div class="p-4">
                <h3 class="text-lg font-medium">${festival.name}</h3>
                <p class="text-sm text-gray-600">${festival.town_name}</p>
                <p class="text-sm mt-2">${festival.description}</p>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-sm text-gray-500">${new Date(festival.date).toLocaleDateString()}</span>
                    <span class="px-2 py-1 rounded text-sm ${festival.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${festival.status}
                    </span>
                </div>
                <div class="mt-4 flex justify-end space-x-2">
                    <button onclick="editFestival(${festival.festival_id})" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="toggleFestivalStatus(${festival.festival_id})" class="text-gray-600 hover:text-gray-800">
                        <i class="fas ${festival.status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off'}"></i>
                    </button>
                </div>
            </div>
        `;
        
        grid.appendChild(card);
    });
}

function openAddFestivalModal() {
    document.getElementById('festivalModal').classList.remove('hidden');
}

function closeFestivalModal() {
    document.getElementById('festivalModal').classList.add('hidden');
    document.getElementById('festivalForm').reset();
}

// Handle festival form submission
document.getElementById('festivalForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(this);
        
        const response = await fetch('../../tripko-backend/api/festival/create.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            alert('Festival added successfully!');
            closeFestivalModal();
            loadFestivals();
        } else {
            throw new Error(result.message || 'Failed to add festival');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error adding festival: ' + error.message);
    }
});

// Handle festival search
document.getElementById('festivalSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const filteredFestivals = festivals.filter(festival => 
        festival.name.toLowerCase().includes(searchTerm) ||
        festival.description.toLowerCase().includes(searchTerm) ||
        festival.town_name.toLowerCase().includes(searchTerm)
    );
    displayFestivals(filteredFestivals);
});

async function toggleFestivalStatus(festivalId) {
    try {
        const response = await fetch('../../tripko-backend/api/festival/toggle_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ festival_id: festivalId })
        });

        const result = await response.json();
        
        if (result.success) {
            loadFestivals(); // Refresh the list
        } else {
            throw new Error(result.message || 'Failed to toggle status');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error toggling festival status: ' + error.message);
    }
}

async function editFestival(festivalId) {
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon!');
}
