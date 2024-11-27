import {
  Anchor,
  Group,
  Progress,
  Table,
  Text,
  Flex,
  SimpleGrid,
  TextInput,
  rem,
  ActionIcon,
  Button,
  Pagination,
  Paper,
} from '@mantine/core';
import { IconSearch, IconArrowRight } from '@tabler/icons-react';
import { useForm } from '@mantine/form';

const data = [
  {
    title: 'Foundation',
    author: 'Isaac Asimov',
    year: 1951,
    reviews: { positive: 2223, negative: 259 },
  },
  {
    title: 'Frankenstein',
    author: 'Mary Shelley',
    year: 1818,
    reviews: { positive: 5677, negative: 1265 },
  },
  {
    title: 'Solaris',
    author: 'Stanislaw Lem',
    year: 1961,
    reviews: { positive: 3487, negative: 1845 },
  },
  {
    title: 'Dune',
    author: 'Frank Herbert',
    year: 1965,
    reviews: { positive: 8576, negative: 663 },
  },
  {
    title: 'The Left Hand of Darkness',
    author: 'Ursula K. Le Guin',
    year: 1969,
    reviews: { positive: 6631, negative: 993 },
  },
  {
    title: 'A Scanner Darkly',
    author: 'Philip K Dick',
    year: 1977,
    reviews: { positive: 8124, negative: 1847 },
  },
];

const rows = data.map((row) => {
  const totalReviews = row.reviews.negative + row.reviews.positive;
  const positiveReviews = (row.reviews.positive / totalReviews) * 100;
  const negativeReviews = (row.reviews.negative / totalReviews) * 100;

  return (
    <Table.Tr key={row.title}>
      <Table.Td>
        <Anchor component="button" fz="sm">
          {row.title}
        </Anchor>
      </Table.Td>
      <Table.Td>{row.year}</Table.Td>
      <Table.Td>
        <Anchor component="button" fz="sm">
          {row.author}
        </Anchor>
      </Table.Td>
      <Table.Td>{Intl.NumberFormat().format(totalReviews)}</Table.Td>
      <Table.Td>
        <Group justify="space-between">
          <Text fz="xs" c="teal" fw={700}>
            {positiveReviews.toFixed(0)}%
          </Text>
          <Text fz="xs" c="red" fw={700}>
            {negativeReviews.toFixed(0)}%
          </Text>
        </Group>
        <Progress.Root>
          <Progress.Section value={positiveReviews} color="teal" />
          <Progress.Section value={negativeReviews} color="red" />
        </Progress.Root>
      </Table.Td>
    </Table.Tr>
  );
});

export default function Agents() {
  const form = useForm({
    initialValues: { search: '' },
  });

  const styleCard = {
    background: 'white',
    borderRadius: rem(8),
    padding: rem(16),
    boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
  };

  return (
    <>
      <Text fw={700} fz="xl" mb="md">
        Agents Management
      </Text>
      <SimpleGrid cols={{ base: 1, sm: 1 }} spacing="lg">
        {/* Actions Section */}
        <SimpleGrid cols={{ base: 1, sm: 3 }} spacing="lg">
          
          <Paper style={styleCard}>
            <Flex gap="sm" align="center">
              <Button fullWidth variant="filled" color="blue">
                Add New Agent
              </Button>
              <Button fullWidth variant="outline">
                Export Data
              </Button>
            </Flex>
          </Paper>

          <div></div>
          {/* Search Section */}
          <Paper style={styleCard}>
            <TextInput
              size="sm"
              radius="md"
              placeholder="Search for agents..."
              rightSectionWidth={42}
              leftSection={<IconSearch size={18} stroke={1.5} />}
              {...form.getInputProps('search')}
              rightSection={
                <ActionIcon size={28} radius="xl" variant="filled" type="submit">
                  <IconArrowRight size={18} stroke={1.5} />
                </ActionIcon>
              }
            />
          </Paper>
          
        </SimpleGrid>

        {/* Table Section */}
            <Table.ScrollContainer style={styleCard} minWidth={800}>
              <Table striped highlightOnHover verticalSpacing="xs">
                <Table.Thead>
                  <Table.Tr>
                    <Table.Th>Book title</Table.Th>
                    <Table.Th>Year</Table.Th>
                    <Table.Th>Author</Table.Th>
                    <Table.Th>Reviews</Table.Th>
                    <Table.Th>Reviews distribution</Table.Th>
                  </Table.Tr>
                </Table.Thead>
                <Table.Tbody>{rows}</Table.Tbody>
              </Table>
            </Table.ScrollContainer>

        {/* Pagination Section */}
        <Paper style={styleCard}>
          <Group position="center">
            <Pagination total={10} size="sm" />
          </Group>
        </Paper>
      </SimpleGrid>
    </>
  );
}
