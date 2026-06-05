import axios, { AxiosInstance } from 'axios';
import { API_BASE_URL, API_SECRET } from '../config';

export interface Alert {
  id: number;
  rule: string;
  priority: 'high' | 'emergency';
  from_address: string;
  subject: string;
  snippet: string;
  timestamp: string;
  sent_at: string | null;
  created_at: string;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: string;
}

class AlertApiService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: `${API_BASE_URL}/api`,
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  }

  async getAlerts(): Promise<Alert[]> {
    try {
      const response = await this.api.get<ApiResponse<Alert[]>>('/alerts', {
        headers: {
          Authorization: `Bearer ${API_SECRET}`,
        },
      });

      if (response.data.success && response.data.data) {
        return response.data.data;
      }
      return [];
    } catch (error) {
      console.error('Error fetching alerts:', error);
      throw error;
    }
  }
}

export default new AlertApiService();
