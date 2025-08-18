import axios, { AxiosError } from 'axios';
import { toast } from 'sonner';

const axiosInstance = axios.create({
  baseURL: import.meta.env.APP_URL || '/',
  headers: {
    'Content-Type': 'application/json',
  },
});

axiosInstance.interceptors.response.use(
  (response) => response,

  async (error: AxiosError) => {
    if ([400, 403, 404, 500].includes(error.status!)) {
      // todo comprehensive error handling
      toast.error(error.response?.data?.message);
    }

    return Promise.reject(error);
  },
);

export default axiosInstance;
