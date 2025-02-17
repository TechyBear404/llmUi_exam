<template>
    <ScrollArea class="h-full">
        <div v-if="conversations.length === 0" class="py-4 text-center">
            <Card class="mx-4 bg-muted/50">
                <CardContent class="pt-6">
                    <font-awesome-icon
                        icon="fa-solid fa-message"
                        class="mb-2 text-4xl text-muted-foreground"
                    />
                    <p class="text-muted-foreground">
                        Aucune conversation pour le moment
                    </p>
                </CardContent>
            </Card>
        </div>

        <div v-else class="p-4 space-y-2">
            <div
                v-for="conversation in conversations"
                :key="conversation.id"
                @click="selectConversation(conversation)"
                class="relative flex items-center p-3 transition-colors rounded-lg cursor-pointer group"
                :class="[
                    conversation.id === currentConversation?.id
                        ? 'bg-primary/80'
                        : 'hover:bg-primary/50',
                ]"
                :title="conversation.title"
            >
                <div class="flex items-center flex-1 min-w-0 space-x-3">
                    <font-awesome-icon
                        icon="fa-solid fa-comments"
                        class="text-muted-foreground"
                    />
                    <div class="flex flex-col min-w-0">
                        <div class="text-sm font-medium truncate">
                            {{ conversation.title || "New Conversation" }}
                        </div>
                        <div class="text-xs opacity-75">
                            {{ getLastMessageDate(conversation) }}
                        </div>
                    </div>
                </div>

                <Button
                    @click.stop="deleteConversation(conversation)"
                    variant="ghost"
                    size="icon"
                    class="absolute w-8 h-8 transition-opacity opacity-0 group-hover:opacity-100 right-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                >
                    <font-awesome-icon
                        icon="fa-solid fa-trash"
                        class="w-4 h-4"
                    />
                </Button>
            </div>
        </div>
    </ScrollArea>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Card, CardContent } from "@/Components/ui/card";
import { formatDateTime } from "@/lib/utils";

const props = defineProps({
    currentConversation: {
        type: Object,
        required: false,
        default: null,
    },
    conversations: {
        type: Array,
        required: true,
        default: () => [],
    },
});

// console.log("Conversations", props.conversations);

const getLastMessageDate = (conversation) => {
    const lastMessage =
        conversation.messages && conversation.messages?.length > 0
            ? conversation.messages[conversation.messages.length - 1]
            : null;
    return formatDateTime(
        lastMessage ? lastMessage.created_at : conversation.created_at
    );
};

const selectConversation = (conversation) => {
    router.get(route("conversations.show", conversation.id));
};

const deleteConversation = async (conversation) => {
    if (!confirm("Êtes-vous sûr de vouloir supprimer cette conversation ?"))
        return;

    try {
        router.delete(route("conversations.destroy", conversation));
    } catch (error) {
        console.error("Error deleting conversation:", error);
    }
};
</script>

<style lang="scss" scoped></style>
