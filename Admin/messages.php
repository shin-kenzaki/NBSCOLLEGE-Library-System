<?php
session_start();
require_once('../db.php');

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

include('inc/header.php');
?>

<div id="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Mobile View Controls -->
            <div class="col-12 d-block d-md-none mb-3">
                <button class="btn btn-primary btn-sm toggle-conversations">
                    <i class="fas fa-chevron-left"></i> Back to Conversations
                </button>
            </div>

            <!-- Conversations List -->
            <div class="col-md-4 conversation-sidebar">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Conversations</h6>
                        <div class="d-flex align-items-center">
                            <div class="input-group input-group-sm mr-2" style="width: 150px;">
                                <input type="text" class="form-control" id="searchConversation" 
                                       placeholder="Search..." aria-label="Search conversations">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" id="newChatBtn" onclick="toggleChatButton(this)">
                                <i class="fas fa-plus"></i> Talk to Someone
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush" id="conversationList" style="height: calc(100vh - 200px); overflow-y: auto;">
                            <!-- Content will be dynamically loaded -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="col-md-8 chat-main">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary" id="chatTitle">Select a conversation</h6>
                    </div>
                    <div class="card-body">
                        <div id="messageArea" style="height: calc(100vh - 280px); overflow-y: auto;" class="mb-4 p-4 border rounded">
                            <div class="text-center text-muted">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Select a conversation to start chatting</p>
                            </div>
                        </div>
                        <form id="messageForm" class="d-none">
                            <div class="input-group">
                                <input type="text" class="form-control" id="messageInput" placeholder="Type your message...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-paper-plane"></i> Send
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Add these styles in the head section or in your CSS file */
.conversation-item.active .text-muted,
.conversation-item.active small,
.user-item.active .text-muted,
.user-item.active small {
    color: rgba(255, 255, 255, 0.75) !important;
}

.conversation-item.active,
.user-item.active {
    background-color: var(--primary) !important;
    color: white !important;
    border-color: var(--primary) !important;
}

.user-item.active h6,
.conversation-item.active h6 {
    color: white !important;
}

/* Add to existing styles */
@media (max-width: 767.98px) {
    .conversation-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        z-index: 1040;
        background: white;
        transition: transform 0.3s ease-in-out;
        transform: translateX(-100%);
    }

    .conversation-sidebar.show {
        transform: translateX(0);
    }

    .chat-main {
        min-height: calc(100vh - 100px);
    }

    .toggle-conversations {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1030;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    #messageArea {
        height: calc(100vh - 200px) !important;
    }

    .card-body {
        padding: 0.5rem;
    }

    #searchConversation {
        width: 120px !important;
    }

    .card-header {
        padding: 0.75rem 0.5rem !important;
    }
}

/* Additional mobile-specific styles */
@media (max-width: 767.98px) {
    .conversation-item, .user-item {
        padding: 0.5rem !important;
    }

    .conversation-item img, .user-item img {
        width: 32px !important;
        height: 32px !important;
    }

    .conversation-item h6, .user-item h6 {
        font-size: 0.9rem !important;
    }

    .conversation-item small, .user-item small {
        font-size: 0.75rem !important;
    }
}
</style>

<script>
// Add these variables at the top of your JavaScript
let selectedRole = null;
let selectedUserId = null;
let selectedName = null;  // Add this line to store the name
let refreshInterval = null;

// Handle conversation selection
document.querySelectorAll('.conversation-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        selectedRole = this.dataset.role;
        document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('chatTitle').textContent = `Chat with ${selectedRole}`;
        document.getElementById('messageForm').classList.remove('d-none');
        loadMessages();
    });
});

// Update loadMessages function to filter by selected role
function loadMessages() {
    if (!selectedRole || !selectedUserId) {
        document.getElementById('messageArea').innerHTML = 
            '<div class="text-center text-muted">Please select a conversation</div>';
        return;
    }
    
    const messageArea = document.getElementById('messageArea');
    messageArea.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
    
    const params = new URLSearchParams({
        user_id: selectedUserId, // Changed from admin_id to user_id
        role: selectedRole
    });
    
    fetch('ajax/get_messages.php?' + params)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to load messages');
            }
            
            // Verify this is still the current chat
            if (selectedUserId != data.current_chat.user_id) {
                return; // Don't update if user switched to different chat
            }
            
            messageArea.innerHTML = '';
            
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const isCurrentUser = msg.sender_id == <?php echo $_SESSION['admin_id']; ?> && 
                                       msg.sender_role == '<?php echo $_SESSION['role']; ?>';
                    const messageElement = document.createElement('div');
                    messageElement.className = `d-flex ${isCurrentUser ? 'justify-content-end' : 'justify-content-start'} mb-3`;
                    messageElement.innerHTML = `
                        <div class="message ${isCurrentUser ? 'ml-auto' : 'mr-auto'}" style="max-width: 70%;">
                            <small class="text-muted ${isCurrentUser ? 'text-right' : 'text-left'} d-block mb-1">
                                ${msg.sender_name}
                            </small>
                            <div class="card ${isCurrentUser ? 'bg-primary text-white' : 'bg-light'}">
                                <div class="card-body py-2 px-3">
                                    <p class="mb-0">${msg.message}</p>
                                </div>
                            </div>
                            <small class="text-muted ${isCurrentUser ? 'text-right' : 'text-left'} d-block mt-1">
                                ${new Date(msg.send_time).toLocaleString()}
                            </small>
                        </div>
                    `;
                    messageArea.appendChild(messageElement);
                });
                messageArea.scrollTop = messageArea.scrollHeight;
            } else {
                messageArea.innerHTML = '<div class="text-center text-muted">No messages yet. Start a conversation!</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            messageArea.innerHTML = `
                <div class="text-center text-danger">
                    <p>Error loading messages: ${error.message}</p>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadMessages()">
                        <i class="fas fa-sync"></i> Try Again
                    </button>
                </div>`;
        });

    // Add this to the end of the loadMessages success callback
    if (window.innerWidth <= 768) {
        setTimeout(() => {
            const messageArea = document.getElementById('messageArea');
            messageArea.scrollTop = messageArea.scrollHeight;
        }, 100);
    }
}

// Send message
document.getElementById('messageForm').onsubmit = function(e) {
    e.preventDefault();
    if (!selectedRole || !selectedUserId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select a recipient first'
        });
        return;
    }
    
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    if (!message) return;

    const sendButton = this.querySelector('button[type="submit"]');
    sendButton.disabled = true;
    
    fetch('ajax/send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: message,
            receiver_role: selectedRole,
            receiver_id: selectedUserId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadMessages();
        } else {
            throw new Error(data.error || 'Failed to send message');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to send message'
        });
    })
    .finally(() => {
        sendButton.disabled = false;
    });
};

// Initial load and refresh
if (selectedRole) {
    loadMessages();
    setInterval(loadMessages, 5000);
}

// Update the search event listener to handle both lists
document.getElementById('searchConversation').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const isUserList = document.querySelector('.user-item') !== null; // Check if we're showing user list
    
    if (isUserList) {
        // Search in users list
        document.querySelectorAll('.user-item').forEach(item => {
            const userName = item.querySelector('h6').textContent.toLowerCase();
            const userRole = item.querySelector('small').textContent.toLowerCase();
            const shouldShow = userName.includes(searchTerm) || userRole.includes(searchTerm);
            item.style.display = shouldShow ? 'block' : 'none';
        });
    } else {
        // Search in conversations list
        document.querySelectorAll('.conversation-item').forEach(item => {
            const name = item.querySelector('h6').textContent.toLowerCase();
            const role = item.querySelector('small').textContent.toLowerCase();
            const shouldShow = name.includes(searchTerm) || role.includes(searchTerm);
            item.style.display = shouldShow ? 'block' : 'none';
        });
    }
});

// Update toggleChatButton to maintain search term when switching views
function toggleChatButton(button) {
    const searchTerm = document.getElementById('searchConversation').value;
    
    if (button.classList.contains('btn-primary')) {
        // Going to user list view
        button.classList.remove('btn-primary');
        button.classList.add('btn-outline-primary');
        button.innerHTML = '<i class="fas fa-times"></i> Back to Conversations';
        loadAllUsers().then(() => {
            if (searchTerm) {
                document.getElementById('searchConversation').dispatchEvent(new Event('input'));
            }
        });
    } else {
        // Going back to conversations view
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary');
        button.innerHTML = '<i class="fas fa-plus"></i> Talk to Someone';
        loadConversations().then(() => {
            if (searchTerm) {
                document.getElementById('searchConversation').dispatchEvent(new Event('input'));
            }
        });
    }
}

// Add this near your other JavaScript code
document.getElementById('newChatBtn').addEventListener('click', function(e) {
    const button = this;
    // Reset any active conversation
    selectedRole = null;
    document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
    
    Swal.fire({
        title: 'Start a New Conversation',
        html: `
            <select id="staffRole" class="form-control">
                <option value="">Select staff role...</option>
                <option value="Admin">Admin</option>
                <option value="Librarian">Librarian</option>
                <option value="Assistant">Assistant</option>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Start Chat',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const role = document.getElementById('staffRole').value;
            if (!role) {
                Swal.showValidationMessage('Please select a staff role');
            }
            return role;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            selectedRole = result.value;
            document.getElementById('chatTitle').textContent = `Chat with ${selectedRole}`;
            document.getElementById('messageForm').classList.remove('d-none');
            loadMessages();
        } else {
            // Reset button style if cancelled
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-primary');
        }
    });
});

// Add this function to your JavaScript section
function toggleChatButton(button) {
    if (button.classList.contains('btn-primary')) {
        button.classList.remove('btn-primary');
        button.classList.add('btn-outline-primary');
        loadAllUsers(); // Show all users when outlined
    } else {
        button.classList.remove('btn-outline-primary');
        button.classList.add('btn-primary');
        loadConversations(); // Show conversations when filled
    }
}

// Add these new functions to your JavaScript section
function loadConversations() {
    const button = document.getElementById('newChatBtn');
    // Ensure button is in primary state
    button.classList.remove('btn-outline-primary');
    button.classList.add('btn-primary');
    button.innerHTML = '<i class="fas fa-plus"></i> Talk to Someone';
    
    return fetch('ajax/get_conversations.php')
        .then(response => response.json())
        .then(data => {
            const conversationList = document.getElementById('conversationList');
            conversationList.innerHTML = '';
            
            // Always show conversations view regardless of data length
            if (!data || data.length === 0) {
                conversationList.innerHTML = `
                    <div class="text-center text-muted p-3">
                        <p>No conversations yet</p>
                        <small>Click "Talk to Someone" to start a new chat</small>
                    </div>`;
                // Clear chat area
                document.getElementById('messageArea').innerHTML = `
                    <div class="text-center text-muted">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <p>Start a new conversation by clicking "Talk to Someone"</p>
                    </div>`;
                return;
            }
            
            // Rest of the conversation display logic
            data.forEach(conv => {
                const unreadBadge = conv.unread > 0 ? 
                    `<span class="badge badge-light">${conv.unread}</span>` : '';
                
                const lastMessageTime = new Date(conv.last_message).toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'numeric',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const lastMessage = conv.last_message_text ? 
                    `<small>${conv.last_messenger}: ${conv.last_message_text}</small>` : 
                    '<small>No messages yet</small>';

                conversationList.innerHTML += `
                    <a href="#" class="list-group-item list-group-item-action conversation-item" 
                       data-id="${conv.id}" data-role="${conv.role}" data-name="${conv.name}" onclick="handleConversationClick(event)">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="${conv.image}" 
                                     class="rounded-circle" style="width: 40px; height: 40px;">
                            </div>
                            <div class="flex-grow-1 ml-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">${conv.name}</h6>
                                    ${conv.unread > 0 ? `<span class="badge badge-primary">${conv.unread}</span>` : ''}
                                </div>
                                <div class="text-truncate text-muted small">
                                    ${conv.last_message_text ? 
                                        `${conv.last_messenger}: ${conv.last_message_text}` : 
                                        'No messages yet'}
                                </div>
                                <small class="text-muted">
                                    ${new Date(conv.last_message).toLocaleString('en-US', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        month: 'short',
                                        day: 'numeric'
                                    })}
                                </small>
                            </div>
                        </div>
                    </a>`;
            });
            
            // Auto-select first conversation if none is selected
            if (!selectedRole && !selectedUserId && data.length > 0) {
                const firstConversation = document.querySelector('.conversation-item');
                if (firstConversation) {
                    selectedUserId = firstConversation.dataset.id;
                    selectedRole = firstConversation.dataset.role;
                    selectedName = firstConversation.dataset.name;
                    firstConversation.classList.add('active');
                    document.getElementById('chatTitle').textContent = 
                        `Chat with ${selectedName} (${selectedRole})`;
                    document.getElementById('messageForm').classList.remove('d-none');
                    loadMessages();
                }
            }

            // Reattach event listeners
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    selectedUserId = this.dataset.id;
                    selectedRole = this.dataset.role;
                    selectedName = this.dataset.name;
                    handleConversationClick(e);
                });
            });
        })
        .catch(error => {
            console.error('Error loading conversations:', error);
            const conversationList = document.getElementById('conversationList');
            conversationList.innerHTML = `
                <div class="text-center text-danger p-3">
                    <p>Error loading conversations</p>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadConversations()">
                        <i class="fas fa-sync"></i> Try Again
                    </button>
                </div>`;
        });
}

// Handle conversation click
window.handleConversationClick = function(e) {
    e.preventDefault();
    const item = e.currentTarget;
    
    // Clear selections first
    selectedUserId = null;
    selectedRole = null;
    selectedName = null;
    
    // Set new selections
    selectedUserId = item.dataset.id;
    selectedRole = item.dataset.role;
    selectedName = item.dataset.name;
    
    document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
    item.classList.add('active');
    
    document.getElementById('chatTitle').textContent = `Chat with ${selectedName}`;
    document.getElementById('messageForm').classList.remove('d-none');
    
    // Save selected chat to session storage
    sessionStorage.setItem('selectedChat', JSON.stringify({
        userId: selectedUserId,
        role: selectedRole,
        name: selectedName
    }));
    
    loadMessages();
    
    // Handle mobile view
    if (window.innerWidth <= 768) {
        document.querySelector('.conversation-sidebar').classList.remove('show');
        setTimeout(() => {
            const messageArea = document.getElementById('messageArea');
            messageArea.scrollTop = messageArea.scrollHeight;
        }, 100);
    }
};

function loadAllUsers() {
    return fetch('ajax/get_all_users.php')
        .then(response => response.json())
        .then(data => {
            const conversationList = document.getElementById('conversationList');
            conversationList.innerHTML = '';
            data.forEach(user => {
                const displayId = user.display_id ? `(${user.display_id})` : '';
                const displayName = `${user.name} ${displayId}`;
                
                conversationList.innerHTML += `
                    <a href="#" class="list-group-item list-group-item-action user-item" 
                       data-id="${user.id}"
                       data-role="${user.role}"
                       data-name="${user.name}"
                       data-unique-key="${user.unique_key}"
                       onclick="selectUser(this)">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="${user.image || 'inc/img/default-avatar.jpg'}" 
                                     class="rounded-circle" style="width: 40px; height: 40px;">
                            </div>
                            <div class="flex-grow-1 ml-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">${user.name}</h6>
                                        <small class="text-muted">${user.role}</small>
                                    </div>
                                </div>
                                <small class="text-muted d-block">${displayId}</small>
                            </div>
                        </div>
                    </a>`;
            });
        });
}

// Add function to handle user selection
function selectUser(element) {
    // Clear previous chat
    document.getElementById('messageArea').innerHTML = 
        '<div class="text-center text-muted"><i class="fas fa-comments fa-3x mb-3"></i><p>Talk to Someone...</p></div>';
    
    const userId = element.dataset.id;
    const role = element.dataset.role;
    const name = element.dataset.name;
    const uniqueKey = element.dataset.uniqueKey;
    
    // Reset previous selection
    selectedUserId = null;
    selectedRole = null;
    selectedName = null;
    
    // Set new selection
    selectedUserId = userId;
    selectedRole = role;
    selectedName = name;
    selectedUniqueKey = uniqueKey;
    
    document.querySelectorAll('.user-item').forEach(i => i.classList.remove('active'));
    element.classList.add('active');
    
    document.getElementById('chatTitle').textContent = `Chat with ${name} (${role})`;
    document.getElementById('messageForm').classList.remove('d-none');
    
    sessionStorage.setItem('selectedChat', JSON.stringify({
        userId: userId,
        role: role,
        name: name,
        uniqueKey: uniqueKey
    }));
    
    loadMessages();
}

// When navigating away or closing chat
function clearCurrentChat() {
    selectedUserId = null;
    selectedRole = null;
    sessionStorage.removeItem('selectedChat');
    document.getElementById('messageForm').classList.add('d-none');
    document.getElementById('chatTitle').textContent = 'Select a conversation';
}

// Add this to handle page load/refresh
window.addEventListener('load', function() {
    const savedChat = sessionStorage.getItem('selectedChat');
    if (savedChat) {
        const chat = JSON.parse(savedChat);
        selectUser({
            dataset: {
                id: chat.userId,
                role: chat.role,
                name: chat.name,
                uniqueKey: chat.uniqueKey
            }
        });
    }
    loadConversations().then(() => {
        // If no saved chat, select first conversation
        if (!savedChat) {
            const firstConversation = document.querySelector('.conversation-item');
            if (firstConversation) {
                handleConversationClick({ currentTarget: firstConversation, preventDefault: () => {} });
            }
        }
    });
    
    // Set up message refresh interval
    refreshInterval = setInterval(loadMessages, 5000);
});

// Clean up on page unload
window.addEventListener('unload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// Update message count badge only if there are unread messages
function updateMessageBadge() {
    const badge = document.getElementById('messageCount');
    fetch('ajax/get_unread_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                badge.style.display = 'inline';
                badge.textContent = data.count;
            } else {
                badge.style.display = 'none';
            }
        });
}

// Update the message refresh logic
function setupMessageRefresh() {
    updateMessageBadge(); // Initial update
    setInterval(updateMessageBadge, 30000); // Update every 30 seconds
}

// Add to window load event
window.addEventListener('load', setupMessageRefresh);

// Initialize by loading conversations
document.addEventListener('DOMContentLoaded', function() {
    const chatButton = document.getElementById('newChatBtn');
    if (chatButton.classList.contains('btn-outline-primary')) {
        // If button is in outline state, switch back to primary
        chatButton.classList.remove('btn-outline-primary');
        chatButton.classList.add('btn-primary');
        chatButton.innerHTML = '<i class="fas fa-plus"></i> Talk to Someone';
    }
    loadConversations();
});

// Add to existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Mobile view handlers
    const conversationSidebar = document.querySelector('.conversation-sidebar');
    const toggleButton = document.querySelector('.toggle-conversations');
    const chatMain = document.querySelector('.chat-main');

    function toggleSidebar() {
        conversationSidebar.classList.toggle('show');
        if (conversationSidebar.classList.contains('show')) {
            toggleButton.innerHTML = '<i class="fas fa-chevron-left"></i> Back';
        } else {
            toggleButton.innerHTML = '<i class="fas fa-comments"></i>';
        }
    }

    toggleButton.addEventListener('click', toggleSidebar);

    // Hide sidebar when conversation is selected on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (e.target.closest('.conversation-item') || e.target.closest('.user-item')) {
                conversationSidebar.classList.remove('show');
                toggleButton.innerHTML = '<i class="fas fa-comments"></i>';
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            conversationSidebar.classList.remove('show');
        }
    });
});

// Add to existing handleConversationClick function
function handleConversationClick(e) {
    // ...existing code...
    
    // Scroll message area to bottom on mobile
    if (window.innerWidth <= 768) {
        setTimeout(() => {
            const messageArea = document.getElementById('messageArea');
            messageArea.scrollTop = messageArea.scrollHeight;
        }, 100);
    }
}
</script>
