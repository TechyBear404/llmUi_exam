<template>
    <div class="space-y-6">
        <h3 class="text-lg font-medium text-primary-foreground">
            Commandes Personnalisées
        </h3>

        <div class="space-y-4">
            <div
                v-for="(command, index) in form.custom_commands"
                :key="index"
                class="p-4 space-y-4 border rounded-lg bg-background/50"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-muted-foreground"
                            >Nom de la commande</label
                        >
                        <Input
                            v-model="command.name"
                            placeholder="Nom de la commande"
                            class="w-full bg-background"
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-muted-foreground"
                            >Description</label
                        >
                        <Input
                            v-model="command.description"
                            placeholder="Description"
                            class="w-full bg-background"
                        />
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-muted-foreground"
                        >Template</label
                    >
                    <Textarea
                        v-model="command.template"
                        placeholder="Template de la commande"
                        rows="3"
                        class="w-full resize-none bg-background"
                    />
                </div>
                <div class="flex justify-end">
                    <Button
                        @click="removeCommand(index)"
                        variant="destructive"
                        size="sm"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-trash"
                            class="mr-2"
                        />
                        Supprimer
                    </Button>
                </div>
            </div>

            <Button
                type="button"
                @click="addCommand"
                variant="outline"
                class="w-full"
            >
                <font-awesome-icon icon="fa-solid fa-plus" class="mr-2" />
                Ajouter une Commande
            </Button>

            <div
                v-if="form.errors.custom_commands"
                class="text-sm text-destructive"
            >
                {{ form.errors.custom_commands }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from "vue";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

const addCommand = () => {
    props.form.custom_commands.push({
        name: "",
        description: "",
        template: "",
    });
};

const removeCommand = (index) => {
    props.form.custom_commands.splice(index, 1);
};
</script>
