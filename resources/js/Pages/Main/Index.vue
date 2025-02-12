<template>
    <AuthenticatedLayout>
        <div class="flex h-[calc(100vh-4rem)] overflow-hidden">
            <!-- Left Sidebar with Conversations -->
            <div class="flex flex-col w-80 border-r bg-muted/50">
                <!-- New Chat Button -->
                <div class="p-4 border-b">
                    <Button
                        @click="createNewConversation"
                        class="w-full"
                        variant="default"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-plus"
                            class="w-4 h-4 mr-2"
                        />
                        <span>New Chat</span>
                    </Button>
                </div>

                <!-- Scrollable conversations list -->
                <div class="flex-1 min-h-0">
                    <Conversations
                        :current-conversation="currentConversation"
                        :conversations="conversations"
                    />
                </div>
            </div>

            <!-- Right Side: Model Selector and Chat -->
            <div class="flex flex-col flex-1 min-w-0">
                <!-- Model Selector Header -->
                <div class="p-4 border-b bg-muted/50">
                    <ModelsSelector
                        v-model="selectedModel"
                        :models="models"
                        @update:model-value="handleModelChange"
                    />
                </div>

                <!-- Chat Area -->
                <div class="flex-1 min-h-0">
                    <Chat
                        v-if="currentConversation"
                        :conversation="currentConversation"
                        :model="selectedModel"
                        class="h-full"
                    />
                    <div
                        v-else
                        class="flex items-center justify-center h-full text-muted-foreground"
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
import ModelsSelector from "./Partials/ModelsSelector.vue";
import { Button } from "@/components/ui/button";

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
