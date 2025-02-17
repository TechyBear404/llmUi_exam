<template>
    <AuthenticatedLayout>
        <!-- Overlay for mobile -->
        <div
            v-if="showConversations"
            class="fixed inset-0 z-10 bg-black/50 md:hidden"
            @click="showConversations = false"
        ></div>

        <div class="flex h-[calc(100vh-4rem)] overflow-hidden w-full relative">
            <!-- Toggle button for mobile -->
            <Button
                v-show="showConversations"
                @click="showConversations = false"
                class="fixed left-[19.5rem] top-20 z-50 md:hidden shadow-md"
                size="icon"
                variant="outline"
            >
                <font-awesome-icon icon="fa-solid fa-times" class="w-4 h-4" />
            </Button>

            <!-- Left Sidebar with Conversations -->
            <Transition name="slide">
                <div
                    v-show="showConversations"
                    class="absolute z-20 flex-col h-full overflow-hidden transition-all duration-300 ease-in-out border-r md:relative bg-muted"
                    :class="[
                        showConversations ? 'left-0 w-80' : '-left-80 w-0',
                    ]"
                >
                    <div class="w-80">
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
                                <span>Nouvelle conversation</span>
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
                </div>
            </Transition>

            <!-- Right Side: Model Selector and Chat -->
            <div
                class="flex flex-col flex-1 w-full min-w-0 transition-all duration-300"
                :class="{ 'md:ml-0': !showConversations }"
            >
                <!-- Model and Instructions Selector Header -->
                <div
                    class="flex items-center justify-between gap-4 p-4 border-b bg-muted"
                >
                    <!-- Toggle Conversations Button -->
                    <Button
                        @click="toggleConversations"
                        class=""
                        size="icon"
                        variant="outline"
                    >
                        <font-awesome-icon
                            :icon="'fa-solid fa-bars'"
                            class="w-4 h-4 p-2"
                        />
                    </Button>

                    <ModelsSelector
                        v-model="selectedModel"
                        :models="models"
                        @update:model-value="handleModelChange"
                        class="flex-1"
                    />
                    <CustomInstructionSelector
                        v-if="currentConversation && customInstructions"
                        v-model="selectedInstruction"
                        :instructions="customInstructions"
                        @update:model-value="handleCustomInstructionChange"
                    />
                </div>

                <!-- Chat Area -->
                <div class="flex-1 min-h-0">
                    <Chat
                        v-if="currentConversation"
                        :conversation="currentConversation"
                        :model="models.find((m) => m.id === selectedModel)"
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
                                Sélectionnez une conversation ou commencez-en
                                une nouvelle
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Conversations from "./Partials/Conversations.vue";
import Chat from "./Partials/Chat.vue";
import ModelsSelector from "./Partials/ModelsSelector.vue";
import CustomInstructionSelector from "./Partials/CustomInsctructionSelector.vue";
import { Button } from "@/Components/ui/button";

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
    customInstructions: {
        type: Array,
        required: false,
        default: null,
    },
});

const selectedModel = ref(
    props.currentConversation?.model_id || (props.models[0]?.id ?? null)
);

const selectedInstruction = ref(
    props.currentConversation?.custom_instruction_id || null
);

// Create a deep copy of the conversation to manage locally
const currentConversation = ref(
    props.currentConversation ? { ...props.currentConversation } : null
);

console.log(props.currentConversation);
// Watch for changes in props.currentConversation and update local state
watch(
    () => props.currentConversation,
    (newConversation) => {
        if (newConversation) {
            currentConversation.value = { ...newConversation };
            selectedModel.value = newConversation.model_id;
            selectedInstruction.value = newConversation.custom_instruction_id;
        } else {
            currentConversation.value = null;
            selectedInstruction.value = null;
        }
    },
    { immediate: true }
);

const handleModelChange = (newModel) => {
    if (newModel) {
        selectedModel.value = newModel;
        // find model with id
        const model = props.models.find((model) => model.id === newModel);
        console.log(model);
        localStorage.setItem("last_used_model", newModel);

        if (props.currentConversation) {
            router.put(
                route("conversations.update", props.currentConversation.id),
                {
                    model: model,
                }
            );
        } else {
            router.put(route("user.update-model"), {
                model: model,
            });
        }
    }
};

const handleCustomInstructionChange = (instructionId) => {
    if (!currentConversation.value) return;

    selectedInstruction.value = instructionId;
    router.put(
        route("conversations.custom-instruction", currentConversation.value.id),
        {
            custom_instruction_id:
                instructionId === null ? null : instructionId,
        }
    );
};

const createNewConversation = () => {
    if (!selectedModel.value) return;

    router.post(route("conversations.store"), {
        model_id: selectedModel.value,
    });
};

const showConversations = ref(window.innerWidth >= 768);

const toggleConversations = () => {
    showConversations.value = !showConversations.value;
};

// Suppression du gestionnaire de redimensionnement pour garder le contrôle manuel
</script>
<style>
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(-100%);
    opacity: 0;
    width: 0;
}

.slide-enter-to,
.slide-leave-from {
    transform: translateX(0);
    opacity: 1;
    width: 20rem;
}
</style>
