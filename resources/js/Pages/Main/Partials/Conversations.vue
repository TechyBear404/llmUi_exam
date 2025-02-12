<template>
    <ScrollArea class="h-full">
        <div v-if="props.conversations.length === 0" class="py-4 text-center">
            <Card class="mx-4 bg-muted/50">
                <CardContent class="pt-6">
                    <font-awesome-icon
                        icon="fa-solid fa-message"
                        class="text-4xl text-muted-foreground mb-2"
                    />
                    <p class="text-muted-foreground">No conversations yet</p>
                </CardContent>
            </Card>
        </div>

        <div v-else class="space-y-2 p-4">
            <Button
                v-for="conversation in props.conversations"
                :key="conversation.id"
                @click="selectConversation(conversation)"
                :variant="
                    currentConversation?.id === conversation.id
                        ? 'secondary'
                        : 'ghost'
                "
                class="w-full group relative"
            >
                <div class="flex items-center flex-1 min-w-0 space-x-3">
                    <font-awesome-icon
                        icon="fa-solid fa-message"
                        class="text-muted-foreground"
                    />
                    <span class="truncate">{{
                        conversation.title || "New Conversation"
                    }}</span>
                </div>

                <Button
                    @click.stop="deleteConversation(conversation)"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity absolute right-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                >
                    <font-awesome-icon
                        icon="fa-solid fa-trash"
                        class="h-4 w-4"
                    />
                </Button>
            </Button>
        </div>
    </ScrollArea>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Card, CardContent } from "@/components/ui/card";

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
