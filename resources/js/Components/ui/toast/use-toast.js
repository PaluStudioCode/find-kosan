import { toast } from 'vue-sonner';

export function useToast() {
    return {
        toast: (opts) => {
            const message = opts.title || 'Notifikasi';
            const options = {};
            if (opts.description) {
                options.description = opts.description;
            }

            if (opts.variant === 'destructive') {
                toast.error(message, options);
            } else {
                toast.success(message, options);
            }
        }
    };
}
