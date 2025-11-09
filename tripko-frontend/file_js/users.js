// Global variables
const currentUserId = null;
let userModal, userForm, accountModal, profileModal, accountForm, profileForm, modalTitle;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DOM elements
    userModal = document.getElementById('userModal');
    userForm = document.getElementById('userForm');
    accountModal = document.getElementById('accountModal');
    profileModal = document.getElementById('profileModal');
    accountForm = document.getElementById('accountForm');
    profileForm = document.getElementById('profileForm');
    modalTitle = document.getElementById('modalTitle');    accountForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const userType = formData.get('user_type');
        
        try {
            // Validate municipality selection for tourism officers
            if (userType === '3' && !formData.get('town_id')) {
                throw new Error('Please select a municipality for the tourism officer');
            }

            const response = await fetch('../../tripko-backend/api/users/create.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.text();
            console.log('Server response:', result);
            
            let data;
            try {
                data = JSON.parse(result);
            } catch (e) {
                console.error('Failed to parse server response:', e);
                throw new Error('Server returned invalid response');
            }

            if (data.success) {
                alert('User account created successfully!');
                accountForm.reset();
                closeAccountModal();
                location.reload(); // Refresh to show new user
            } else {
                throw new Error(data.message || 'Failed to create user account');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error creating user account: ' + error.message);
        }
    });

    const createUserType = document.getElementById('user_type');
    const editUserType = document.getElementById('edit_user_type');

    if (createUserType) {
        createUserType.addEventListener('change', () => handleUserTypeChange(''));
    }
    if (editUserType) {
        editUserType.addEventListener('change', () => handleUserTypeChange('edit_'));
    }
});

// Handle user type change
function handleUserTypeChange(prefix = '') {
    const userType = document.getElementById(prefix + 'user_type').value;
    const municipalityField = document.getElementById(prefix + 'municipalityField');
    const municipalitySelect = document.getElementById(prefix + 'municipality');
    
    if (userType === '3') { // Tourism Officer
        municipalityField.classList.remove('hidden');
        municipalitySelect.required = true;
        loadMunicipalities(prefix);
    } else {
        municipalityField.classList.add('hidden');
        municipalitySelect.required = false;
    }
}

// Load municipalities for selection
async function loadMunicipalities(prefix = '') {
    try {
        const response = await fetch('../../tripko-backend/api/towns/read.php');
        const data = await response.json();
        const municipalitySelect = document.getElementById(prefix + 'municipality');
        
        if (data.success && data.records && Array.isArray(data.records)) {
            municipalitySelect.innerHTML = '<option value="" disabled selected>Select municipality</option>';
            data.records.forEach(town => {
                const option = document.createElement('option');
                option.value = town.town_id;
                option.textContent = town.name;
                municipalitySelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load municipalities:', error);
        const municipalitySelect = document.getElementById(prefix + 'municipality');
        municipalitySelect.innerHTML = '<option value="" disabled selected>Error loading municipalities</option>';
    }
}

// Modal functions
function openAccountModal() {
    document.getElementById('accountModal').classList.remove('hidden');
}

function closeAccountModal() {
    document.getElementById('accountModal').classList.add('hidden');
}

function openProfileModal() {
    document.getElementById('profileModal').classList.remove('hidden');
}

function closeProfileModal() {
    document.getElementById('profileModal').classList.add('hidden');
}

function openStatusModal(userId, currentStatus) {
    currentUserId = userId;
    const statusText = document.getElementById('currentStatusText');
    statusText.textContent = currentStatus || 'active';
    statusText.className = 'font-semibold ' + 
        (currentStatus === 'inactive' ? 'text-red-600' : 'text-green-600');
    document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
    currentUserId = null;
}

async function updateUserStatus(newStatus) {
    if (!currentUserId) return;

    try {
        const response = await fetch('../../tripko-backend/api/users/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: currentUserId,
                status: newStatus
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert(`User status updated to ${newStatus}`);
            closeStatusModal();
            location.reload(); // Refresh to show updated status
        } else {
            throw new Error(data.message || 'Failed to update status');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        alert('Failed to update user status: ' + error.message);
    }
}
