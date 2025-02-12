<template>
    <AuthenticatedLayout>
        <!-- Remove h-screen since AuthenticatedLayout already provides full height -->
        <div class="flex flex-1 bg-gray-900">
            <!-- Left Sidebar -->
            <div class="flex flex-col flex-shrink-0 w-80 bg-gray-800 border-r border-gray-700">
                <!-- Fixed header section -->
                <div class="p-4 space-y-4">
                    <!-- Model Selector -->
                    <select
                        v-model="selectedModel"
                        @change="handleModelChange"
                        class="w-full p-2 text-white bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500"
                    >
                        <option
                            v-for="model in models"
                            :key="model.id"
                            :value="model.id"
                        >
                            {{ model.name }}
                        </option>
                    </select>

                    <!-- New Chat Button -->
                    <button
                        @click="createNewConversation"
                        class="flex items-center justify-center w-full px-4 py-2 space-x-2 font-medium text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700"
                    >
                        <font-awesome-icon icon="fa-solid fa-plus" />
                        <span>New Chat</span>
                    </button>
                </div>

                <!-- Scrollable conversations list -->
                <div class="flex-1 overflow-y-auto">
                    <Conversations
                        :current-conversation="currentConversation"
                        :conversations="conversations"
                    />
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="flex flex-col flex-1">
                <div class="relative flex-1 overflow-hidden">
                    <Chat
                        v-if="currentConversation"
                        :conversation="currentConversation"
                        :model="selectedModel"
                        class="absolute inset-0"
                    />
                    <div
                        v-else
                        class="flex items-center justify-center h-full text-gray-400"
                    >
                        <div class="text-center">
                            <font-awesome-icon
                                icon="fa-solid fa-message"
                                class="mb-4 text-4xl"
                            />
                            <p class="text-xl">
                                Select a conversation or start a new one
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Conversations from "./Partials/Conversations.vue";
import Chat from "./Partials/Chat.vue";

const props = defineProps({
    models: {
        type: Array,
        required: true,
    },
    conversations: {
        type: Array,
        required: true,
    },
    currentConversation: {
        type: Object,
        required: false,
        default: null,
    },
});

const selectedModel = ref(
    props.currentConversation?.model_id || (props.models[0]?.id ?? null)
);

const handleModelChange = () => {
    if (selectedModel.value) {
        localStorage.setItem("last_used_model", selectedModel.value);
    }
};

const createNewConversation = () => {
    if (!selectedModel.value) return;

    router.post(route("conversations.store"), {
        model_id: selectedModel.value,
    });
};
</script>
