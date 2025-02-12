<template>
    <div class="flex-1 overflow-y-auto space-y-2">
        <div
            v-if="props.conversations.length === 0"
            class="text-center text-gray-400 py-4"
        >
            No conversations yet
        </div>

        <div
            v-else
            v-for="conversation in props.conversations"
            :key="conversation.id"
            @click="selectConversation(conversation)"
            class="flex items-center justify-between p-3 hover:bg-gray-800 rounded-lg cursor-pointer transition-colors"
            :class="{
                'bg-gray-700': currentConversation?.id === conversation.id,
            }"
        >
            <div class="flex items-center space-x-3 flex-1 min-w-0">
                <font-awesome-icon
                    icon="fa-solid fa-message"
                    class="text-gray-400"
                />
                <span class="truncate text-gray-200">{{
                    conversation.title || "New Conversation"
                }}</span>
            </div>
            <button
                @click.stop="deleteConversation(conversation)"
                class="text-gray-400 hover:text-red-500 p-2 rounded-full hover:bg-gray-700/50 transition-colors"
            >
                <font-awesome-icon icon="fa-solid fa-trash" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
    currentConversation: {
        type: Object,
        required: false,
        default: null,
    },
    conversations: {
        type: Array,
        required: true,
    },
});

const selectConversation = (conversation) => {
    router.get(route("conversations.show", conversation.id));
};

const deleteConversation = async (conversation) => {
    if (!confirm("Are you sure you want to delete this conversation?")) return;

    try {
        await router.delete(route("conversations.destroy", conversation.id));
    } catch (error) {
        console.error("Error deleting conversation:", error);
    }
};
</script>

<style lang="scss" scoped></style>
