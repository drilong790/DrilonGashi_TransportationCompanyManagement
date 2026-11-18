export type User = {
  id: number;
  full_name: string;
  email: string;
  role: 'SUPERADMIN' | 'ADMIN' | 'DISPATCHER';
};

export type TransportStatus = 'PLANNED' | 'IN_PROGRESS' | 'COMPLETED' | 'CANCELLED';

export type Transport = {
  id: number;
  customer_id: number;
  customer_name: string;
  driver_id: number | null;
  driver_name: string | null;
  vehicle_id: number | null;
  vehicle_plate: string | null;
  pickup_address: string;
  destination_address: string;
  cargo_description: string;
  scheduled_date: string;
  scheduled_end: string;
  status: TransportStatus;
  created_by_name: string;
};

export type Resource = {
  id: number;
  label: string;
};

export type TransportResources = {
  customers: Resource[];
  drivers: Resource[];
  vehicles: Resource[];
};

export type TransportInput = {
  customer_id: number;
  driver_id: number | null;
  vehicle_id: number | null;
  pickup_address: string;
  destination_address: string;
  cargo_description: string;
  scheduled_date: string;
  scheduled_end: string;
  status: TransportStatus;
};

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}
