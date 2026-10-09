const ones = [
  'Zero',
  'One',
  'Two',
  'Three',
  'Four',
  'Five',
  'Six',
  'Seven',
  'Eight',
  'Nine',
  'Ten',
  'Eleven',
  'Twelve',
  'Thirteen',
  'Fourteen',
  'Fifteen',
  'Sixteen',
  'Seventeen',
  'Eighteen',
  'Nineteen',
]
const tens = [
  '',
  '',
  'Twenty',
  'Thirty',
  'Forty',
  'Fifty',
  'Sixty',
  'Seventy',
  'Eighty',
  'Ninety',
]
const scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion']

const underThousand = (value) => {
  const words = []
  if (value >= 100) {
    words.push(ones[Math.floor(value / 100)], 'Hundred')
    value %= 100
  }
  if (value >= 20) {
    words.push(`${tens[Math.floor(value / 10)]}${value % 10 ? `-${ones[value % 10]}` : ''}`)
  } else if (value > 0 || words.length === 0) {
    words.push(ones[value])
  }
  return words.join(' ')
}

const integerInWords = (value) => {
  if (value === 0) return ones[0]
  const groups = []
  let scaleIndex = 0
  while (value > 0) {
    const group = value % 1000
    if (group) groups.unshift(`${underThousand(group)}${scales[scaleIndex] ? ` ${scales[scaleIndex]}` : ''}`)
    value = Math.floor(value / 1000)
    scaleIndex += 1
  }
  return groups.join(' ')
}

export const amountInWords = (amount) => {
  const cents = Math.round(Number(amount || 0) * 100)
  const rupees = Math.floor(cents / 100)
  const paisa = cents % 100
  const rupeeWords = integerInWords(rupees)
  return `Rupees ${rupeeWords}${paisa ? ` and ${integerInWords(paisa)} Paisa` : ''} Only`
}
