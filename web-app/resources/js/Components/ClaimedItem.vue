<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Combined Found Items and Claims Section -->
        <div v-if="combinedItems.length" class="space-y-6">
            <div v-for="item in combinedItems" :key="item.id" class="bg-white rounded-lg shadow-lg p-6">
                
                <!-- Claims Management Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800">Claims Management</h2>
                    <div class="flex space-x-2">
                        <v-btn
                            v-for="filter in ['All', 'Pending', 'Approved', 'Rejected']"
                            :key="filter"
                            :color="currentFilter === filter.toLowerCase() ? 'primary' : 'grey'"
                            @click="currentFilter = filter.toLowerCase()"
                            small
                            outlined
                            class="rounded-md"
                        >
                            {{ filter }}
                        </v-btn>
                    </div>
                </div>

                <!-- Item Details Section -->
                <div class="flex items-center space-x-6">
                    <!-- Item Image -->
                    <div class="w-24 h-24 flex-shrink-0 rounded-lg overflow-hidden">
                        <img 
                            :src="item.image_url && item.image_url.startsWith('/') ? item.image_url : '/' + item.image_url" 
                            :alt="item.item_name" 
                            class="w-full h-full object-cover"
                            @error="handleImageError"
                        />
                    </div>

                    <!-- Item Details -->
                    <div class="flex-grow">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-800">{{ item.item_name }}</h3>
                            <span :class="[ 'px-3 py-1 rounded-full text-sm font-medium', {
                                    'bg-yellow-100 text-yellow-800': item.claim_status === 'pending',
                                    'bg-green-100 text-green-800': item.claim_status === 'approved',
                                    'bg-red-100 text-red-800': item.claim_status === 'rejected'

                                }]">
                                {{ item.claim_status ? item.claim_status.charAt(0).toUpperCase() + item.claim_status.slice(1) : 'Unknown' }}
                            </span>
                        </div>

                        <p class="text-gray-600 text-sm mb-4">{{ item.description }}</p>

                        <div class="text-sm text-gray-500 mb-2">
                            <p><strong>Category:</strong> {{ item.category }}</p>
                            <p v-if="item.found_date"><strong>Found Date:</strong> {{ formatDate(item.found_date) }}</p>
                            <p v-if="item.submission_date"><strong>Claim Submission Date:</strong> {{ formatDate(item.submission_date) }}</p>
                        </div>

                        <div class="text-sm text-gray-500 mb-4">
                            <p><strong>Submitted By:</strong> {{ item.user_name }}</p>
                        </div>

                        <div class="text-sm text-gray-500">
                            <p v-if="item.contact_number"><strong>Contact Number:</strong> {{ item.contact_number }}</p>
                            <p v-if="item.proof_of_ownership">
                                <strong>Proof of Ownership:</strong>
                                <button @click="viewProof(item.proof_of_ownership)" class="text-teal-600 hover:underline text-sm">
                                    View Proof Image
                                </button>
                            </p>
                        </div>

                        <!-- Action Buttons for Approval/Rejection -->
                        <div class="mt-6 flex space-x-4">
                            <button @click="updateClaimStatus(item.claim_id, 'Approved')" class="w-1/2 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Approve
                            </button>
                            <button @click="updateClaimStatus(item.claim_id, 'Rejected')" class="w-1/2 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Claims List: Display filtered claims for each item -->
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-700 mb-4">Claims</h4>
                    <div v-for="claim in filteredClaims" :key="claim.claim_id" class="bg-gray-100 p-4 rounded-lg mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <p class="text-sm font-semibold text-gray-700">Claim #{{ claim.claim_id }}</p>
                            <p :class="[ 'px-3 py-1 rounded-full text-sm font-medium', {
                                    'bg-yellow-100 text-yellow-800': claim.claim_status === 'pending',
                                    'bg-green-100 text-green-800': claim.claim_status === 'approved',
                                    'bg-red-100 text-red-800': claim.claim_status === 'rejected'
                                }]">
                                {{ claim.claim_status ? claim.claim_status.charAt(0).toUpperCase() + claim.claim_status.slice(1) : 'Unknown' }}
                            </p>
                        </div>
                        <button @click="viewProof(claim.proof_of_ownership)" class="text-teal-600 hover:underline text-sm">
                            View Proof of Ownership
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

// State for combined items and current filter
const currentFilter = ref('all');
const claims = ref([]);

// Filtered claims based on the selected filter
const filteredClaims = computed(() => {
  if (currentFilter.value === 'all') {
    return claims.value;
  }
  return claims.value.filter(claim => claim.claim_status.toLowerCase() === currentFilter.value);
});

// Handle image loading error
const handleImageError = (e) => {
    console.error('Error loading image:', e.target.src);
    // Set a fallback image or handle the error as needed
    e.target.src = '/assets/default-item.png'; // Make sure you have this fallback image
};

// Helper function to get correct image URL
const getImageUrl = (url) => {
    if (!url) return '';
    
    // If it's already an absolute URL
    if (url.startsWith('http')) return url;
    
    // If it's a storage URL
    if (url.startsWith('/storage')) return url;
    
    // If it starts with public/
    if (url.startsWith('public/')) {
        return '/' + url.substring(7);
    }
    
    // If it doesn't have a leading slash
    if (!url.startsWith('/')) {
        return '/storage/' + url;
    }
    
    return url;
};

// Handle claim status updates
const handleStatusUpdate = ({ id, status }) => {
  const claimIndex = claims.value.findIndex(claim => claim.claim_id === id);
  if (claimIndex !== -1) {
    claims.value[claimIndex] = { ...claims.value[claimIndex], claim_status: status };
    currentFilter.value = currentFilter.value; // Triggers reactivity
  }
};

// State for combined items
const combinedItems = ref([]);

// Fetch items from the API
const fetchItems = async () => {
    try {
        const foundItemsResponse = await fetch('/found-items');
        const foundItemsData = await foundItemsResponse.json();
        
        const claimsResponse = await fetch('/claim-items');
        const claimsData = await claimsResponse.json();

        const usersResponse = await fetch('/users');
        const usersData = await usersResponse.json();
        
        const usersMap = usersData.reduce((map, user) => {
            map[user.id] = user.name;
            return map;
        }, {});

        const items = foundItemsData.map(foundItem => {
            const claim = claimsData.find(c => c.item_id === foundItem.id);
            if (claim) {
                // Ensure image_url has leading slash
                const imageUrl = foundItem.image_url && !foundItem.image_url.startsWith('/') 
                    ? '/' + foundItem.image_url 
                    : foundItem.image_url;

                return {
                    ...foundItem,
                    claim_id: claim.id,
                    claim_status: claim.claim_status,
                    proof_of_ownership: claim.proof_of_ownership,
                    submission_date: claim.submission_date,
                    user_name: usersMap[claim.user_id] || 'Unknown',
                    image_url: imageUrl
                };
            }
            return null;
        }).filter(item => item !== null);

        combinedItems.value = items;
        claims.value = claimsData;
    } catch (error) {
        console.error('Error fetching items:', error);
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

// Show proof image in modal
const viewProof = (proofUrl) => {
    if (!proofUrl) {
        console.error('No proof URL provided');
        return;
    }

    const fullUrl = getImageUrl(proofUrl);
    console.log('Opening image URL:', fullUrl);
    window.open(window.location.origin + fullUrl, '_blank');
};

// Update the claim status (approve or reject)
const updateClaimStatus = async (claimId, status) => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch(`/claims/${claimId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ claim_status: status })
        });
        if (response.ok) {
            handleStatusUpdate({ id: claimId, status });
        } else {
            console.error('Error updating claim status');
        }
    } catch (error) {
        console.error('Error updating claim status:', error);
    }
};

onMounted(fetchItems);
</script>

<style scoped>
.container {
    max-width: 1200px;
    margin: 0 auto;
}

.bg-white {
    background-color: #ffffff;
}

.bg-gray-100 {
    background-color: #f7fafc;
}

.bg-green-100 {
    background-color: #d1fae5;
}

.bg-red-100 {
    background-color: #fee2e2;
}

.bg-yellow-100 {
    background-color: #fef9c3;
}

.text-teal-600 {
    color: #4fd1c5;
}

.text-gray-600 {
    color: #718096;
}

.text-gray-700 {
    color: #2d3748;
}

.text-gray-800 {
    color: #1a202c;
}

.text-sm {
    font-size: 0.875rem;
}

.text-xl {
    font-size: 1.25rem;
}

.text-2xl {
    font-size: 1.5rem;
}

.font-semibold {
    font-weight: 600;
}

.font-medium {
    font-weight: 500;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.m-4 {
    margin: 1rem;
}

.mb-4 {
    margin-bottom: 1rem;
}

.mb-6 {
    margin-bottom: 1.5rem;
}

.p-4 {
    padding: 1rem;
}

.p-6 {
    padding: 1.5rem;
}

.rounded-lg {
    border-radius: 0.5rem;
}

.shadow-lg {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>
