<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';

type NoteRow = {
    id: number;
    body: string;
    author: string;
    time: string;
};

defineProps<{
    notes: NoteRow[];
    action: string;
}>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Notes</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4">
            <p v-if="notes.length === 0" class="text-sm text-muted-foreground">
                No notes yet.
            </p>
            <ul v-else class="divide-y text-sm" data-test="notes">
                <li
                    v-for="note in notes"
                    :key="note.id"
                    class="flex items-start justify-between gap-4 py-3 first:pt-0"
                >
                    <p>
                        {{ note.body }}
                        <span class="text-muted-foreground">
                            — {{ note.author }}
                        </span>
                    </p>
                    <span class="shrink-0 text-muted-foreground">
                        {{ note.time }}
                    </span>
                </li>
            </ul>

            <Form
                :action="action"
                method="post"
                reset-on-success
                preserve-scroll
                class="grid gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="note-body">Note</Label>
                    <textarea
                        id="note-body"
                        name="body"
                        required
                        rows="3"
                        placeholder="Add a note"
                        data-test="note-body"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-none outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                    <InputError :message="errors.body" />
                </div>
                <div class="flex justify-end">
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="add-note"
                    >
                        Add note
                    </Button>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
